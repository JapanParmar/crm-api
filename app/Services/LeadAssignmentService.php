<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\User;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Attendance;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadAssignmentService
{
    /**
     * Auto-assign a lead based on routing rules and set the SLA timer.
     */
    public function autoAssign(Lead $lead): ?User
    {
        // 1. Get all active employees
        $candidates = User::role('employee')
            ->where('is_active', true)
            ->whereHas('employee', function ($query) {
                $query->where('status', 'active');
            })
            ->with(['employee', 'managedProjects'])
            ->get();

        if ($candidates->isEmpty()) {
            Log::warning("No active employees found to assign lead {$lead->lead_number}");
            return null;
        }

        // 2. Determine who is clocked in today (Availability)
        $today = today()->toDateString();
        $clockedInUserIds = Attendance::whereDate('date', $today)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->pluck('user_id')
            ->toArray();

        $scoredCandidates = $candidates->map(function (User $user) use ($lead, $clockedInUserIds) {
            $score = 0;
            $employee = $user->employee;

            // Rule A: Availability (+10 points if clocked in today)
            $isClockedIn = in_array($user->id, $clockedInUserIds);
            if ($isClockedIn) {
                $score += 10;
            }

            // Rule B: Location/Branch Match (+5 points if lead city matches employee address/city)
            if ($lead->city && $employee && $employee->address) {
                if (stripos($employee->address, $lead->city) !== false) {
                    $score += 5;
                }
            }

            // Rule C: Project Match (+15 points if the lead is interested in a project managed by this user)
            if ($lead->project_id) {
                $project = Project::find($lead->project_id);
                if ($project && $project->manager_id === $user->id) {
                    $score += 15;
                }
            }

            // Rule D: Workload Balancer (Subtract points for active leads to prevent overload)
            // Active leads are those not in 'closed_won' or 'closed_lost'
            $activeLeadsCount = Lead::where('assigned_to', $user->id)
                ->whereNotIn('status', ['closed_won', 'closed_lost'])
                ->count();
            
            // Subtract 1 point per active lead (up to a max penalty of -10)
            $score -= min($activeLeadsCount, 10);

            // Rule E: Budget-based matching (High-end designation gets high budgets)
            // Budget > 1 Crore (10,000,000) goes to Manager/Senior Designation
            if ($lead->budget_max && $lead->budget_max >= 10000000 && $employee) {
                $designation = strtolower($employee->designation);
                if (str_contains($designation, 'manager') || str_contains($designation, 'senior') || str_contains($designation, 'lead')) {
                    $score += 8;
                }
            }

            return [
                'user' => $user,
                'score' => $score,
                'active_leads' => $activeLeadsCount,
            ];
        });

        // Sort by score (descending), then by workload (ascending) for round-robin balancing
        $sorted = $scoredCandidates->sortByDesc(function ($item) {
            return $item['score'];
        })->values();

        $selected = $sorted->first();
        if (!$selected) {
            return null;
        }

        $assignedUser = $selected['user'];

        // SLA expiration duration: 15 minutes
        $slaMinutes = 15;

        // Update Lead with assignment
        $lead->update([
            'assigned_to'       => $assignedUser->id,
            'assigned_at'       => now(),
            'assignment_status' => 'pending',
            'sla_expires_at'    => now()->addMinutes($slaMinutes),
        ]);

        // Log the activity
        ActivityLog::create([
            'lead_id'      => $lead->id,
            'performed_by' => auth('api')->id() ?? 1, // Fallback to system/admin user
            'type'         => 'assigned',
            'description'  => "Lead automatically routed to {$assignedUser->name} (SLA: {$slaMinutes} mins).",
            'metadata'     => [
                'assigned_to' => $assignedUser->id,
                'sla_expires_at' => $lead->sla_expires_at->toIso8601String(),
                'rule_scores' => $sorted->map(fn($item) => ['name' => $item['user']->name, 'score' => $item['score']])->toArray()
            ]
        ]);

        return $assignedUser;
    }

    /**
     * Handle lead reassignment on SLA expiration.
     */
    public function reassignOnSlaExpiry(Lead $lead): ?User
    {
        $previousAgentId = $lead->assigned_to;
        $previousAgentName = $lead->assignedTo?->name ?? 'Unknown Agent';

        // Mark current assignment as expired
        $lead->update([
            'assignment_status' => 'expired',
        ]);

        // Log SLA expiration activity
        ActivityLog::create([
            'lead_id'      => $lead->id,
            'performed_by' => 1, // System
            'type'         => 'sla_expired',
            'description'  => "SLA expired for {$previousAgentName}. Auto-reassigning lead...",
            'metadata'     => [
                'expired_agent_id' => $previousAgentId,
            ]
        ]);

        // Find next candidate, excluding the previous agent
        $candidates = User::role('employee')
            ->where('is_active', true)
            ->where('id', '!=', $previousAgentId)
            ->whereHas('employee', function ($query) {
                $query->where('status', 'active');
            })
            ->with(['employee', 'managedProjects'])
            ->get();

        if ($candidates->isEmpty()) {
            Log::warning("No alternative agents found for reassignment of lead {$lead->lead_number}");
            
            // Notify manager (we log this as an activity for dashboard alerts)
            ActivityLog::create([
                'lead_id'      => $lead->id,
                'performed_by' => 1, // System
                'type'         => 'manager_alert',
                'description'  => "Manager Alert: SLA expired for {$previousAgentName} on lead {$lead->lead_number}, but no alternative agent is available.",
                'metadata'     => [
                    'lead_id' => $lead->id,
                    'previous_agent_id' => $previousAgentId,
                ]
            ]);
            return null;
        }

        // Apply scoring (same rules, but candidate list excludes previous agent)
        $today = today()->toDateString();
        $clockedInUserIds = Attendance::whereDate('date', $today)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->pluck('user_id')
            ->toArray();

        $scoredCandidates = $candidates->map(function (User $user) use ($lead, $clockedInUserIds) {
            $score = 0;
            $employee = $user->employee;

            $isClockedIn = in_array($user->id, $clockedInUserIds);
            if ($isClockedIn) $score += 10;

            if ($lead->city && $employee && $employee->address) {
                if (stripos($employee->address, $lead->city) !== false) $score += 5;
            }

            if ($lead->project_id) {
                $project = Project::find($lead->project_id);
                if ($project && $project->manager_id === $user->id) $score += 15;
            }

            $activeLeadsCount = Lead::where('assigned_to', $user->id)
                ->whereNotIn('status', ['closed_won', 'closed_lost'])
                ->count();
            $score -= min($activeLeadsCount, 10);

            if ($lead->budget_max && $lead->budget_max >= 10000000 && $employee) {
                $designation = strtolower($employee->designation);
                if (str_contains($designation, 'manager') || str_contains($designation, 'senior') || str_contains($designation, 'lead')) {
                    $score += 8;
                }
            }

            return [
                'user' => $user,
                'score' => $score,
            ];
        });

        $sorted = $scoredCandidates->sortByDesc('score')->values();
        $selected = $sorted->first();
        if (!$selected) {
            return null;
        }

        $newAgent = $selected['user'];
        $slaMinutes = 15;

        // Assign to new agent
        $lead->update([
            'assigned_to'       => $newAgent->id,
            'assigned_at'       => now(),
            'assignment_status' => 'pending',
            'sla_expires_at'    => now()->addMinutes($slaMinutes),
            'reassigned_from'   => $previousAgentId,
        ]);

        // Log reassigned activity and manager notification alert
        ActivityLog::create([
            'lead_id'      => $lead->id,
            'performed_by' => 1, // System
            'type'         => 'reassigned',
            'description'  => "Lead reassigned from {$previousAgentName} to {$newAgent->name} due to SLA timeout.",
            'metadata'     => [
                'reassigned_from' => $previousAgentId,
                'reassigned_to'   => $newAgent->id,
                'sla_expires_at'  => $lead->sla_expires_at->toIso8601String()
            ]
        ]);

        return $newAgent;
    }
}
