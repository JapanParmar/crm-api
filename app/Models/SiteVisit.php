<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SiteVisit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_id',
        'attended_by',
        'project_name',
        'location',
        'status',
        'scheduled_at',
        'completed_at',
        'notes',
        'feedback',
        'interested',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'interested'   => 'boolean',
    ];

    public const STATUSES = ['scheduled', 'completed', 'cancelled', 'no_show'];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function attendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attended_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }
}
