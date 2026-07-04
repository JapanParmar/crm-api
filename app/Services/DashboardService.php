<?php

namespace App\Services;

use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\SiteVisit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Stats for the admin dashboard.
     */
    public function adminStats(): array
    {
        $now = now();

        $totalLeads   = Lead::count();
        $assignedLeads = Lead::whereNotNull('assigned_to')->count();
        $newLeads     = Lead::byStatus('new')->count();
        $newToday     = Lead::createdToday()->count();
        $closedWon    = Lead::byStatus('closed_won')->count();
        $closedLost   = Lead::byStatus('closed_lost')->count();
        $activeLeads  = Lead::whereNotIn('status', ['closed_won', 'closed_lost'])->count();

        $pendingFollowUps = FollowUp::scheduled()->count();
        $overdueFollowUps = FollowUp::overdue()->count();
        $todayFollowUps   = FollowUp::today()->where('status', 'scheduled')->count();

        $todaySiteVisits     = SiteVisit::today()->where('status', 'scheduled')->count();
        $activeEmployees     = User::where('is_active', true)->role('employee')->count();

        // Conversion rate
        $conversionRate = $totalLeads > 0
            ? round(($closedWon / $totalLeads) * 100, 1)
            : 0;

        // Leads by source (top 8)
        $leadsBySource = Lead::select('source', DB::raw('count(*) as count'))
            ->groupBy('source')
            ->orderByDesc('count')
            ->limit(8)
            ->pluck('count', 'source')
            ->toArray();

        // Leads by status
        $leadsByStatus = Lead::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Cold leads: no contact in 5+ days and not closed
        $coldLeads = Lead::whereNotIn('status', ['closed_won', 'closed_lost'])
            ->where(function ($q) {
                $q->whereNull('last_contacted_at')
                  ->orWhere('last_contacted_at', '<', now()->subDays(5));
            })
            ->count();

        // Missed follow-ups
        $missedFollowUps = FollowUp::where('status', 'missed')->count();

        return [
            'total_leads'          => $totalLeads,
            'assigned_leads'       => $assignedLeads,
            'unassigned_leads'     => $totalLeads - $assignedLeads,
            'new_leads'            => $newLeads,
            'new_today'            => $newToday,
            'active_leads'         => $activeLeads,
            'closed_won'           => $closedWon,
            'closed_lost'          => $closedLost,
            'conversion_rate'      => $conversionRate,
            'pending_follow_ups'   => $pendingFollowUps,
            'overdue_follow_ups'   => $overdueFollowUps,
            'today_follow_ups'     => $todayFollowUps,
            'missed_follow_ups'    => $missedFollowUps,
            'today_site_visits'    => $todaySiteVisits,
            'active_employees'     => $activeEmployees,
            'cold_leads'           => $coldLeads,
            'leads_by_source'      => $leadsBySource,
            'leads_by_status'      => $leadsByStatus,
        ];
    }

    /**
     * Stats for an employee dashboard (scoped to their own data).
     */
    public function employeeStats(int $userId): array
    {
        $myLeads          = Lead::where('assigned_to', $userId)->count();
        $myPendingFollowUps = FollowUp::where('assigned_to', $userId)->scheduled()->count();
        $myOverdueFollowUps = FollowUp::where('assigned_to', $userId)->overdue()->count();
        $myTodayFollowUps = FollowUp::where('assigned_to', $userId)->today()->where('status', 'scheduled')->count();
        $myTodaySiteVisits = SiteVisit::where('attended_by', $userId)->today()->where('status', 'scheduled')->count();
        $myClosedWon      = Lead::where('assigned_to', $userId)->byStatus('closed_won')->count();

        return [
            'my_leads'              => $myLeads,
            'my_pending_follow_ups' => $myPendingFollowUps,
            'my_overdue_follow_ups' => $myOverdueFollowUps,
            'my_today_follow_ups'   => $myTodayFollowUps,
            'my_today_site_visits'  => $myTodaySiteVisits,
            'my_closed_won'         => $myClosedWon,
        ];
    }

    /**
     * Team performance data for admin dashboard.
     */
    public function teamPerformance(): array
    {
        $employees = User::role('employee')
            ->where('is_active', true)
            ->get(['id', 'name', 'email']);

        return $employees->map(function (User $user) {
            $assignedLeads   = Lead::where('assigned_to', $user->id)->count();
            $closedDeals     = Lead::where('assigned_to', $user->id)->byStatus('closed_won')->count();
            $pendingFollowUps = FollowUp::where('assigned_to', $user->id)->scheduled()->count();

            $conversionRate = $assignedLeads > 0
                ? round(($closedDeals / $assignedLeads) * 100, 1)
                : 0;

            return [
                'id'               => $user->id,
                'name'             => $user->name,
                'email'            => $user->email,
                'assigned_leads'   => $assignedLeads,
                'closed_deals'     => $closedDeals,
                'conversion_rate'  => $conversionRate,
                'pending_follow_ups' => $pendingFollowUps,
            ];
        })->sortByDesc('assigned_leads')->values()->toArray();
    }

    /**
     * Today's scheduled follow-ups for dashboard widget (admin sees all, employee sees own).
     */
    public function todaySchedule(?int $userId = null): array
    {
        $query = FollowUp::with(['lead:id,name,phone', 'assignedTo:id,name'])
            ->where('status', 'scheduled')
            ->whereDate('scheduled_at', today())
            ->orderBy('scheduled_at');

        if ($userId) {
            $query->where('assigned_to', $userId);
        }

        return $query->limit(20)->get()->map(function (FollowUp $fu) {
            return [
                'id'               => $fu->id,
                'lead_id'          => $fu->lead_id,
                'lead_name'        => $fu->lead?->name,
                'phone'            => $fu->lead?->phone,
                'type'             => $fu->type,
                'status'           => $fu->status,
                'scheduled_at'     => $fu->scheduled_at,
                'notes'            => $fu->notes,
                'assigned_to_id'   => $fu->assigned_to,
                'assigned_to_name' => $fu->assignedTo?->name,
            ];
        })->toArray();
    }
}
