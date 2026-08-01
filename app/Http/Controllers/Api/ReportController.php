<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use App\Models\Project;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\FollowUp;
use App\Models\SiteVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Get Lead Performance Analytics.
     */
    public function leadPerformance(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // Ingestion rate daily
        $dailyIngestion = Lead::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Source effectiveness
        $sourceEffectiveness = Lead::select('source', DB::raw('count(*) as total'), 
            DB::raw('SUM(CASE WHEN status = "closed_won" THEN 1 ELSE 0 END) as won'),
            DB::raw('SUM(CASE WHEN status = "closed_lost" THEN 1 ELSE 0 END) as lost')
        )
            ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate])
            ->groupBy('source')
            ->get()
            ->map(function ($item) {
                $item->conversion_rate = $item->total > 0 ? round(($item->won / $item->total) * 100, 1) : 0;
                return $item;
            });

        return response()->json([
            'success' => true,
            'data' => [
                'daily_ingestion' => $dailyIngestion,
                'source_effectiveness' => $sourceEffectiveness,
            ]
        ]);
    }

    /**
     * Get Sales Performance Analytics.
     */
    public function salesPerformance(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());

        // Revenue by project
        $revenueByProject = Project::select('projects.id', 'projects.name', 
            DB::raw('count(leads.id) as won_leads_count'),
            DB::raw('sum(projects.budget) as estimated_revenue')
        )
            ->join('leads', 'projects.id', '=', 'leads.project_id')
            ->where('leads.status', 'closed_won')
            ->whereBetween(DB::raw('DATE(leads.updated_at)'), [$startDate, $endDate])
            ->groupBy('projects.id', 'projects.name')
            ->get();

        // Deal cycle times (average difference in days between lead creation and closed_won)
        $avgCycleDays = Lead::where('status', 'closed_won')
            ->whereNotNull('accepted_at')
            ->whereBetween(DB::raw('DATE(updated_at)'), [$startDate, $endDate])
            ->select(DB::raw('AVG(TIMESTAMPDIFF(DAY, created_at, updated_at)) as avg_days'))
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'revenue_by_project' => $revenueByProject,
                'avg_cycle_days' => round($avgCycleDays->avg_days ?? 0, 1),
            ]
        ]);
    }

    /**
     * Get Employee Performance Analytics.
     */
    public function employeePerformance(Request $request)
    {
        $employees = User::role('employee')
            ->where('is_active', true)
            ->with(['employee'])
            ->get();

        $data = $employees->map(function ($user) {
            $assigned = Lead::where('assigned_to', $user->id)->count();
            $won = Lead::where('assigned_to', $user->id)->where('status', 'closed_won')->count();
            $lost = Lead::where('assigned_to', $user->id)->where('status', 'closed_lost')->count();
            
            // Site visits handled vs. completed
            $visitsScheduled = SiteVisit::where('attended_by', $user->id)->count();
            $visitsCompleted = SiteVisit::where('attended_by', $user->id)->where('status', 'completed')->count();

            $conversionRate = $assigned > 0 ? round(($won / $assigned) * 100, 1) : 0;
            $visitRate = $visitsScheduled > 0 ? round(($visitsCompleted / $visitsScheduled) * 100, 1) : 0;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'designation' => $user->employee?->designation ?? 'Sales Executive',
                'assigned_leads' => $assigned,
                'won_leads' => $won,
                'lost_leads' => $lost,
                'conversion_rate' => $conversionRate,
                'site_visits_scheduled' => $visitsScheduled,
                'site_visits_completed' => $visitsCompleted,
                'site_visit_conversion_rate' => $visitRate,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get Project Performance Analytics.
     */
    public function projectPerformance(Request $request)
    {
        $projects = Project::select('id', 'name', 'type', 'status', 'total_units', 'available_units')
            ->get()
            ->map(function ($project) {
                $interestCount = Lead::where('project_id', $project->id)->count();
                $wonCount = Lead::where('project_id', $project->id)->where('status', 'closed_won')->count();
                $avgBudget = Lead::where('project_id', $project->id)->avg('budget_max');

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'type' => $project->type,
                    'status' => $project->status,
                    'total_units' => $project->total_units,
                    'available_units' => $project->available_units,
                    'leads_interested' => $interestCount,
                    'leads_closed' => $wonCount,
                    'average_budget' => round($avgBudget ?? 0, 0),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    /**
     * Get Inventory Analytics.
     */
    public function inventoryReports(Request $request)
    {
        $inventoryStats = Project::select(
            DB::raw('SUM(total_units) as total_units'),
            DB::raw('SUM(available_units) as available_units'),
            DB::raw('SUM(sold_units) as sold_units'),
            DB::raw('SUM(sold_units * price_min) as total_sales_value') // Estimated sales value
        )->first();

        $projectBreakdown = Project::select('id', 'name', 'total_units', 'available_units', 'sold_units')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_units' => intval($inventoryStats->total_units ?? 0),
                    'available_units' => intval($inventoryStats->available_units ?? 0),
                    'sold_units' => intval($inventoryStats->sold_units ?? 0),
                    'total_sales_value' => floatval($inventoryStats->total_sales_value ?? 0),
                ],
                'projects' => $projectBreakdown
            ]
        ]);
    }

    /**
     * Get Marketing/Campaign Effectiveness Analytics.
     */
    public function marketingPerformance(Request $request)
    {
        // Filter by marketing sources
        $marketingSources = ['meta_ads', 'google_ads', 'facebook', 'instagram', 'website'];

        $data = Lead::select('source', 
            DB::raw('count(*) as leads_count'),
            DB::raw('SUM(CASE WHEN status = "closed_won" THEN 1 ELSE 0 END) as sales_count')
        )
            ->whereIn('source', $marketingSources)
            ->groupBy('source')
            ->get()
            ->map(function ($item) {
                $item->conversion_rate = $item->leads_count > 0 ? round(($item->sales_count / $item->leads_count) * 100, 1) : 0;
                return $item;
            });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get SLA Adherence Analytics.
     */
    public function slaAdherence(Request $request)
    {
        $employees = User::role('employee')
            ->where('is_active', true)
            ->get();

        $data = $employees->map(function ($user) {
            $totalAssigned = Lead::where('assigned_to', $user->id)->count();
            
            $accepted = Lead::where('assigned_to', $user->id)
                ->where('assignment_status', 'accepted')
                ->count();

            $rejected = Lead::where('assigned_to', $user->id)
                ->where('assignment_status', 'rejected')
                ->count();

            $expired = Lead::where('assigned_to', $user->id)
                ->where('assignment_status', 'expired')
                ->count();

            // Average response/acceptance time in minutes
            $avgAcceptTime = Lead::where('assigned_to', $user->id)
                ->where('assignment_status', 'accepted')
                ->whereNotNull('assigned_at')
                ->whereNotNull('accepted_at')
                ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, assigned_at, accepted_at)) as avg_mins'))
                ->first();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'total_assigned' => $totalAssigned,
                'accepted_count' => $accepted,
                'rejected_count' => $rejected,
                'expired_count' => $expired,
                'sla_breach_rate' => $totalAssigned > 0 ? round(($expired / $totalAssigned) * 100, 1) : 0,
                'avg_acceptance_time_mins' => round($avgAcceptTime->avg_mins ?? 0, 1),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
