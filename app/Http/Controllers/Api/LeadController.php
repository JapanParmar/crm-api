<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\FollowUpResource;
use App\Http\Resources\LeadResource;
use App\Http\Resources\SiteVisitResource;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeadController extends Controller
{
    public function __construct(private LeadService $leadService) {}

    /**
     * GET /api/leads
     * Paginated list with search/filter/sort.
     * Admin sees all; employee sees only assigned.
     */
    public function index(Request $request): JsonResponse
    {
        $user  = auth('api')->user();
        $query = Lead::with('assignedTo:id,name,email');

        // Role scope
        if ($user->hasRole('employee')) {
            $query->where('assigned_to', $user->id);
        }

        // Tab filter
        match ($request->input('tab')) {
            'my'         => $query->where('assigned_to', $user->id),
            'unassigned' => $query->whereNull('assigned_to'),
            'today'      => $query->createdToday(),
            default      => null,
        };

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('lead_number', 'like', "%{$search}%");
            });
        }

        // Filters (accept comma-separated or array values)
        $filterFields = ['status', 'source', 'priority', 'property_type', 'assigned_to'];
        foreach ($filterFields as $field) {
            if ($values = $request->input($field)) {
                $values = is_array($values) ? $values : explode(',', $values);
                $query->whereIn($field, $values);
            }
        }

        // Date range
        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        // Budget range
        if ($min = $request->input('budget_min')) {
            $query->where('budget_max', '>=', $min);
        }
        if ($max = $request->input('budget_max')) {
            $query->where('budget_min', '<=', $max);
        }

        // Sort
        $sortBy  = in_array($request->input('sort_by'), ['created_at', 'updated_at', 'name', 'score', 'priority'])
            ? $request->input('sort_by')
            : 'created_at';
        $sortDir = $request->input('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $limit = min((int) $request->input('limit', 25), 100);
        $leads = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'message' => 'Leads retrieved.',
            'data'    => LeadResource::collection($leads->items()),
            'meta'    => [
                'page'        => $leads->currentPage(),
                'limit'       => $leads->perPage(),
                'total'       => $leads->total(),
                'total_pages' => $leads->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/leads/counts
     * Tab counts for the leads list header.
     */
    public function counts(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $base = $user->hasRole('employee')
            ? Lead::where('assigned_to', $user->id)
            : Lead::query();

        return response()->json([
            'success' => true,
            'data'    => [
                'all'        => (clone $base)->count(),
                'my'         => Lead::where('assigned_to', $user->id)->count(),
                'unassigned' => (clone $base)->whereNull('assigned_to')->count(),
                'today'      => (clone $base)->createdToday()->count(),
            ],
        ]);
    }

    /**
     * POST /api/leads
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $lead = $this->leadService->create(
            $request->validated(),
            auth('api')->id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Lead created successfully.',
            'data'    => new LeadResource($lead),
        ], 201);
    }

    /**
     * GET /api/leads/{lead}
     */
    public function show(Lead $lead): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user->can('view-all-leads') && $lead->assigned_to !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have access to this lead.'
            ], 403);
        }

        $lead->load('assignedTo:id,name,email', 'createdBy:id,name');

        return response()->json([
            'success' => true,
            'data'    => new LeadResource($lead),
        ]);
    }

    /**
     * PATCH /api/leads/{lead}
     */
    public function update(UpdateLeadRequest $request, Lead $lead): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user->can('view-all-leads') && $lead->assigned_to !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have access to this lead.'
            ], 403);
        }

        $lead = $this->leadService->update($lead, $request->validated(), auth('api')->id());

        return response()->json([
            'success' => true,
            'message' => 'Lead updated.',
            'data'    => new LeadResource($lead),
        ]);
    }

    /**
     * DELETE /api/leads/{lead}
     */
    public function destroy(Lead $lead): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user->can('delete-leads') || (!$user->can('view-all-leads') && $lead->assigned_to !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to delete this lead.'
            ], 403);
        }

        $lead->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lead deleted.',
        ]);
    }

    /**
     * GET /api/leads/{lead}/follow-ups
     */
    public function followUps(Lead $lead): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user->can('view-all-leads') && $lead->assigned_to !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have access to this lead.'
            ], 403);
        }

        $followUps = $lead->followUps()
            ->with('assignedTo:id,name')
            ->orderByDesc('scheduled_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => FollowUpResource::collection($followUps),
        ]);
    }

    /**
     * GET /api/leads/{lead}/site-visits
     */
    public function siteVisits(Lead $lead): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user->can('view-all-leads') && $lead->assigned_to !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have access to this lead.'
            ], 403);
        }

        $visits = $lead->siteVisits()
            ->with('attendedBy:id,name')
            ->orderByDesc('scheduled_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => SiteVisitResource::collection($visits),
        ]);
    }

    /**
     * GET /api/leads/{lead}/activity
     */
    public function activity(Lead $lead): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user->can('view-all-leads') && $lead->assigned_to !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have access to this lead.'
            ], 403);
        }

        $logs = $lead->activityLogs()
            ->with('performedBy:id,name')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => ActivityLogResource::collection($logs),
        ]);
    }

    /**
     * PATCH /api/leads/bulk-assign
     */
    public function bulkAssign(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user->can('assign-leads')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. You do not have permission to assign leads.'
            ], 403);
        }

        $validated = $request->validate([
            'lead_ids'    => ['required', 'array'],
            'lead_ids.*'  => ['exists:leads,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $leadIds    = $validated['lead_ids'];
        $assignedTo = $validated['assigned_to'];

        $assigneeName = null;
        if ($assignedTo) {
            $assignee = \App\Models\User::find($assignedTo);
            $assigneeName = $assignee?->name;
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($leadIds, $assignedTo, $user, $assigneeName) {
            foreach ($leadIds as $id) {
                $lead = Lead::find($id);
                if ($lead) {
                    $lead->update([
                        'assigned_to' => $assignedTo,
                        'assigned_at' => $assignedTo ? now() : null,
                    ]);

                    \App\Models\ActivityLog::create([
                        'lead_id'      => $lead->id,
                        'performed_by' => $user->id,
                        'type'         => 'assigned',
                        'description'  => $assignedTo 
                            ? "Lead assigned to {$assigneeName} via bulk action." 
                            : "Lead unassigned via bulk action.",
                        'metadata'     => ['assigned_to' => $assignedTo],
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Leads assignment updated successfully.',
        ]);
    }
}

