<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use App\Services\LeadAssignmentService;
use Illuminate\Support\Facades\Log;

class CheckLeadsSla extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:check-sla';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check leads with pending assignments and reassign them if they have expired the SLA acceptance window.';

    /**
     * Execute the console command.
     */
    public function handle(LeadAssignmentService $assignmentService)
    {
        $this->info('Starting SLA check for leads...');

        $expiredLeads = Lead::where('assignment_status', 'pending')
            ->whereNotNull('sla_expires_at')
            ->where('sla_expires_at', '<=', now())
            ->get();

        $count = $expiredLeads->count();
        $this->info("Found {$count} expired pending leads.");

        if ($count > 0) {
            foreach ($expiredLeads as $lead) {
                $this->info("Reassigning lead {$lead->lead_number}...");
                $newAgent = $assignmentService->reassignOnSlaExpiry($lead);
                if ($newAgent) {
                    $this->line("-> Lead {$lead->lead_number} successfully reassigned to {$newAgent->name}.");
                } else {
                    $this->warn("-> Lead {$lead->lead_number} reassignment failed (no candidates found). Alerted manager.");
                }
            }
        }

        $this->info('SLA check completed.');
        return Command::SUCCESS;
    }
}
