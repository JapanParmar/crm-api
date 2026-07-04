<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogService
{
    public function log(
        ?int $leadId,
        ?int $performedBy,
        string $type,
        string $description,
        array $metadata = []
    ): ActivityLog {
        return ActivityLog::create([
            'lead_id'      => $leadId,
            'performed_by' => $performedBy,
            'type'         => $type,
            'description'  => $description,
            'metadata'     => empty($metadata) ? null : $metadata,
        ]);
    }
}
