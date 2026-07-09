<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSiteVisitRequest;
use App\Http\Resources\SiteVisitResource;
use App\Models\Lead;
use App\Models\SiteVisit;
use App\Services\SiteVisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteVisitController extends Controller
{
    public function __construct(private SiteVisitService $service) {}

    /**
     * GET /api/site-visits
     */
    public function index(Request $request): JsonResponse
    {
        $user  = auth('api')->user();
        $query = SiteVisit::with(['lead:id,name,phone', 'attendedBy:id,name']);

        if ($user->hasRole('employee')) {
            $query->where('attended_by', $user->id);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('lead', fn($lq) => $lq
                      ->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                  );
            });
        }

        $query->orderByDesc('scheduled_at');

        $limit  = min((int) $request->input('limit', 25), 100);
        $visits = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'data'    => SiteVisitResource::collection($visits->items()),
            'meta'    => [
                'page'        => $visits->currentPage(),
                'limit'       => $visits->perPage(),
                'total'       => $visits->total(),
                'total_pages' => $visits->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/site-visits/counts
     */
    public function counts(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $base = SiteVisit::query();
        if ($user->hasRole('employee')) {
            $base->where('attended_by', $user->id);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'all'       => (clone $base)->count(),
                'scheduled' => (clone $base)->where('status', 'scheduled')->count(),
                'completed' => (clone $base)->where('status', 'completed')->count(),
                'no_show'   => (clone $base)->where('status', 'no_show')->count(),
                'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
            ],
        ]);
    }

    /**
     * POST /api/leads/{lead}/site-visits
     */
    public function store(StoreSiteVisitRequest $request, Lead $lead): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user->can('view-all-leads') && $lead->assigned_to != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have access to this lead.'
            ], 403);
        }

        $visit = $this->service->schedule($lead, $request->validated(), auth('api')->id());

        return response()->json([
            'success' => true,
            'message' => 'Site visit scheduled.',
            'data'    => new SiteVisitResource($visit),
        ], 201);
    }

    /**
     * PATCH /api/site-visits/{siteVisit}/complete
     */
    public function complete(Request $request, SiteVisit $siteVisit): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user->can('view-all-leads') && $siteVisit->attended_by != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have access to this site visit.'
            ], 403);
        }

        $validated = $request->validate([
            'feedback'   => ['nullable', 'string'],
            'interested' => ['required', 'boolean'],
            'notes'      => ['nullable', 'string'],
        ]);

        $visit = $this->service->complete($siteVisit, $validated, auth('api')->id());

        return response()->json([
            'success' => true,
            'message' => 'Site visit completed.',
            'data'    => new SiteVisitResource($visit),
        ]);
    }
}
