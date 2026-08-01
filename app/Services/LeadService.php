<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Str;

class LeadService
{
    public function __construct(
        private ActivityLogService $activityLog,
        private LeadAssignmentService $assignmentService
    ) {}

    /**
     * Generate a unique sequential lead number: LID-1001, LID-1002, ...
     */
    public function generateLeadNumber(): string
    {
        $last = Lead::withTrashed()->max('id') ?? 0;
        return 'LID-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new lead and log the activity.
     */
    public function create(array $data, int $createdBy): Lead
    {
        // Guard against duplicate lead creation from any backend path
        $phone = $data['phone'] ?? null;
        $alternatePhone = $data['alternate_phone'] ?? null;
        $email = $data['email'] ?? null;

        if ($phone || $alternatePhone || ($email && trim($email) !== '')) {
            $duplicateQuery = Lead::query();
            $duplicateQuery->where(function ($query) use ($phone, $alternatePhone, $email) {
                if ($phone) {
                    $query->orWhere('phone', $phone)->orWhere('alternate_phone', $phone);
                }
                if ($alternatePhone) {
                    $query->orWhere('phone', $alternatePhone)->orWhere('alternate_phone', $alternatePhone);
                }
                if ($email && trim($email) !== '') {
                    $query->orWhere('email', $email);
                }
            });
            $existing = $duplicateQuery->first();
            if ($existing) {
                throw new \InvalidArgumentException("Duplicate lead detected: Phone/Email matches existing Lead {$existing->lead_number} ({$existing->name}).");
            }
        }

        $data['lead_number'] = $this->generateLeadNumber();
        $data['created_by']  = $createdBy;
        $data['status']      = $data['status'] ?? 'new';
        $data['priority']    = $data['priority'] ?? 'medium';
        $data['score']       = $data['score'] ?? 0;

        $lead = Lead::create($data);

        $this->activityLog->log(
            $lead->id,
            $createdBy,
            'lead_created',
            "Lead {$lead->lead_number} created.",
            ['source' => $lead->source]
        );

        // Auto-assign lead if no assigned_to is manually supplied
        if (empty($lead->assigned_to)) {
            $this->assignmentService->autoAssign($lead);
            $lead->refresh();
        }

        return $lead->load('assignedTo', 'createdBy');
    }

    /**
     * Update a lead and log status changes.
     */
    public function update(Lead $lead, array $data, int $updatedBy): Lead
    {
        $oldStatus = $lead->status;

        $lead->update($data);

        if (isset($data['status']) && $data['status'] !== $oldStatus) {
            $this->activityLog->log(
                $lead->id,
                $updatedBy,
                'status_changed',
                "Status changed from {$oldStatus} → {$data['status']}.",
                ['from' => $oldStatus, 'to' => $data['status']]
            );
        }

        if (isset($data['assigned_to']) && $data['assigned_to'] !== $lead->getOriginal('assigned_to')) {
            $assignee = User::find($data['assigned_to']);
            $this->activityLog->log(
                $lead->id,
                $updatedBy,
                'assigned',
                "Lead assigned to {$assignee?->name}.",
                ['assigned_to' => $data['assigned_to']]
            );
        }

        return $lead->refresh()->load('assignedTo', 'createdBy');
    }

    /**
     * Assign lead to an employee.
     */
    public function assign(Lead $lead, int $assignedTo, int $performedBy): Lead
    {
        $assignee = User::findOrFail($assignedTo);

        $lead->update([
            'assigned_to' => $assignedTo,
            'assigned_at' => now(),
        ]);

        $this->activityLog->log(
            $lead->id,
            $performedBy,
            'assigned',
            "Lead assigned to {$assignee->name}.",
            ['assigned_to_id' => $assignedTo, 'assigned_to_name' => $assignee->name]
        );

        return $lead->refresh()->load('assignedTo');
    }
}
