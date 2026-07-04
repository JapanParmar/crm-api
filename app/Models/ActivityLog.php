<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = true;

    // No updated_at — activity logs are immutable
    const UPDATED_AT = null;

    protected $fillable = [
        'lead_id',
        'performed_by',
        'type',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public const TYPES = [
        'lead_created',
        'status_changed',
        'follow_up_scheduled',
        'follow_up_completed',
        'follow_up_missed',
        'site_visit_scheduled',
        'site_visit_completed',
        'note_added',
        'assigned',
        'call_made',
        'email_sent',
        'whatsapp_sent',
        'lead_imported',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
