<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    /**
     * GET /api/dashboard
     * Returns role-appropriate stats.
     */
    public function index(): JsonResponse
    {
        $user = auth('api')->user();

        if ($user->hasRole('admin')) {
            $stats        = $this->dashboard->adminStats();
            $todaySchedule = $this->dashboard->todaySchedule();
            $team         = $this->dashboard->teamPerformance();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved.',
                'data'    => [
                    'role'           => 'admin',
                    'stats'          => $stats,
                    'today_schedule' => $todaySchedule,
                    'team'           => $team,
                ],
            ]);
        }

        // Employee view — scoped to their own data
        $stats        = $this->dashboard->employeeStats($user->id);
        $todaySchedule = $this->dashboard->todaySchedule($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Dashboard data retrieved.',
            'data'    => [
                'role'           => 'employee',
                'stats'          => $stats,
                'today_schedule' => $todaySchedule,
            ],
        ]);
    }
}
