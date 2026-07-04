<?php

namespace App\Services;

use App\Models\FollowUp;
use App\Models\Lead;

class FollowUpService
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function schedule(Lead $lead, array $data, int $createdBy): FollowUp
    {
        $data['lead_id']    = $lead->id;
        $data['created_by'] = $createdBy;
        $data['status']     = 'scheduled';

        if (empty($data['assigned_to'])) {
            $data['assigned_to'] = $lead->assigned_to ?? $createdBy;
        }

        $followUp = FollowUp::create($data);

        // Update denormalized counter and next follow-up
        $lead->increment('follow_up_count');
        $lead->update(['next_follow_up_at' => $followUp->scheduled_at]);

        $this->activityLog->log(
            $lead->id,
            $createdBy,
            'follow_up_scheduled',
            "Follow-up ({$followUp->type}) scheduled for {$followUp->scheduled_at->format('d M Y H:i')}.",
            ['follow_up_id' => $followUp->id, 'type' => $followUp->type]
        );

        return $followUp->load('assignedTo', 'lead');
    }

    public function complete(FollowUp $followUp, array $data, int $performedBy): FollowUp
    {
        $followUp->update(array_merge($data, [
            'status'       => 'completed',
            'completed_at' => now(),
        ]));

        // Update lead's last contacted
        $followUp->lead->update(['last_contacted_at' => now()]);

        $this->activityLog->log(
            $followUp->lead_id,
            $performedBy,
            'follow_up_completed',
            "Follow-up ({$followUp->type}) marked as completed.",
            ['follow_up_id' => $followUp->id, 'outcome' => $data['outcome'] ?? null]
        );

        return $followUp->refresh()->load('assignedTo', 'lead');
    }

    public function markMissed(FollowUp $followUp, int $performedBy): FollowUp
    {
        $followUp->update(['status' => 'missed']);

        $this->activityLog->log(
            $followUp->lead_id,
            $performedBy,
            'follow_up_missed',
            "Follow-up ({$followUp->type}) marked as missed.",
            ['follow_up_id' => $followUp->id]
        );

        return $followUp->refresh();
    }
}
