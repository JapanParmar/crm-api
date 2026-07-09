<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFollowUpRequest;
use App\Http\Resources\FollowUpResource;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Services\FollowUpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowUpController extends Controller
{
    public function __construct(private FollowUpService $service) {}

    /**
     * GET /api/follow-ups
     * All follow-ups with tab/search/sort. Employee sees only own.
     */
    public function index(Request $request): JsonResponse
    {
        $user  = auth('api')->user();
        $query = FollowUp::with(['lead:id,name,phone', 'assignedTo:id,name']);

        if ($user->hasRole('employee')) {
            $query->where('assigned_to', $user->id);
        }

        // Tab filter
        match ($request->input('tab')) {
            'today'     => $query->today()->where('status', 'scheduled'),
            'upcoming'  => $query->where('status', 'scheduled')->where('scheduled_at', '>', now()),
            'overdue'   => $query->overdue(),
            'missed'    => $query->where('status', 'missed'),
            'completed' => $query->where('status', 'completed'),
            default     => null,
        };

        // Search
        if ($search = $request->input('search')) {
            $query->whereHas('lead', fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
            );
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $query->orderBy('scheduled_at', $request->input('tab') === 'completed' ? 'desc' : 'asc');

        $limit      = min((int) $request->input('limit', 25), 100);
        $followUps  = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'data'    => FollowUpResource::collection($followUps->items()),
            'meta'    => [
                'page'        => $followUps->currentPage(),
                'limit'       => $followUps->perPage(),
                'total'       => $followUps->total(),
                'total_pages' => $followUps->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/follow-ups/counts
     */
    public function counts(Request $request): JsonResponse
    {
        $user  = auth('api')->user();
        $base  = FollowUp::query();
        if ($user->hasRole('employee')) {
            $base->where('assigned_to', $user->id);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'today'     => (clone $base)->today()->where('status', 'scheduled')->count(),
                'upcoming'  => (clone $base)->where('status', 'scheduled')->where('scheduled_at', '>', now())->count(),
                'overdue'   => (clone $base)->overdue()->count(),
                'missed'    => (clone $base)->where('status', 'missed')->count(),
                'completed' => (clone $base)->where('status', 'completed')->count(),
                'all'       => (clone $base)->count(),
            ],
        ]);
    }

    /**
     * POST /api/leads/{lead}/follow-ups
     */
    public function store(StoreFollowUpRequest $request, Lead $lead): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user->can('view-all-leads') && $lead->assigned_to != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have access to this lead.'
            ], 403);
        }

        $followUp = $this->service->schedule($lead, $request->validated(), auth('api')->id());

        return response()->json([
            'success' => true,
            'message' => 'Follow-up scheduled.',
            'data'    => new FollowUpResource($followUp),
        ], 201);
    }

    /**
     * PATCH /api/follow-ups/{followUp}/complete
     */
    public function complete(Request $request, FollowUp $followUp): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user->can('view-all-leads') && $followUp->assigned_to != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have access to this follow-up.'
            ], 403);
        }

        $validated = $request->validate([
            'outcome' => ['nullable', 'string'],
            'notes'   => ['nullable', 'string'],
        ]);

        $followUp = $this->service->complete($followUp, $validated, auth('api')->id());

        return response()->json([
            'success' => true,
            'message' => 'Follow-up marked as completed.',
            'data'    => new FollowUpResource($followUp),
        ]);
    }

    /**
     * PATCH /api/follow-ups/{followUp}/miss
     */
    public function miss(FollowUp $followUp): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user->can('view-all-leads') && $followUp->assigned_to != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have access to this follow-up.'
            ], 403);
        }

        $followUp = $this->service->markMissed($followUp, auth('api')->id());

        return response()->json([
            'success' => true,
            'message' => 'Follow-up marked as missed.',
            'data'    => new FollowUpResource($followUp),
        ]);
    }
}
