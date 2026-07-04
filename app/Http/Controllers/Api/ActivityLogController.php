<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * GET /api/activity
     * Admin sees all; employee sees own performed logs.
     */
    public function index(Request $request): JsonResponse
    {
        $user  = auth('api')->user();
        $query = ActivityLog::with(['lead:id,name,lead_number', 'performedBy:id,name']);

        if ($user->hasRole('employee')) {
            $query->where('performed_by', $user->id);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($leadId = $request->input('lead_id')) {
            $query->where('lead_id', $leadId);
        }

        $query->orderByDesc('created_at');

        $limit = min((int) $request->input('limit', 50), 200);
        $logs  = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'data'    => ActivityLogResource::collection($logs->items()),
            'meta'    => [
                'page'        => $logs->currentPage(),
                'limit'       => $logs->perPage(),
                'total'       => $logs->total(),
                'total_pages' => $logs->lastPage(),
            ],
        ]);
    }
}
