<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\SiteVisit;

class SiteVisitService
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function schedule(Lead $lead, array $data, int $createdBy): SiteVisit
    {
        $data['lead_id']    = $lead->id;
        $data['created_by'] = $createdBy;
        $data['status']     = 'scheduled';

        if (empty($data['attended_by'])) {
            $data['attended_by'] = $lead->assigned_to ?? $createdBy;
        }

        $visit = SiteVisit::create($data);

        $lead->increment('site_visit_count');

        $this->activityLog->log(
            $lead->id,
            $createdBy,
            'site_visit_scheduled',
            "Site visit scheduled at {$visit->project_name} on {$visit->scheduled_at->format('d M Y H:i')}.",
            ['site_visit_id' => $visit->id, 'project' => $visit->project_name]
        );

        return $visit->load('attendedBy', 'lead');
    }

    public function complete(SiteVisit $visit, array $data, int $performedBy): SiteVisit
    {
        $visit->update(array_merge($data, [
            'status'       => 'completed',
            'completed_at' => now(),
        ]));

        $this->activityLog->log(
            $visit->lead_id,
            $performedBy,
            'site_visit_completed',
            "Site visit at {$visit->project_name} completed. " . ($visit->interested ? 'Lead is interested.' : 'Lead not interested.'),
            ['site_visit_id' => $visit->id, 'interested' => $visit->interested]
        );

        return $visit->refresh()->load('attendedBy', 'lead');
    }
}
