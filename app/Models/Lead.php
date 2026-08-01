<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_number',
        'name',
        'phone',
        'alternate_phone',
        'email',
        'lead_date',
        'source',
        'service_type',
        'status',
        'priority',
        'project_id',
        'property_type',
        'budget_min',
        'budget_max',
        'preferred_location',
        'city',
        'locality',
        'project_interest',
        'bhk_preference',
        'score',
        'notes',
        'listing_id',
        'lead_provider_ref',
        'tags',
        'assigned_to',
        'assigned_at',
        'assignment_status',
        'accepted_at',
        'sla_expires_at',
        'reassigned_from',
        'last_contacted_at',
        'next_follow_up_at',
        'follow_up_count',
        'site_visit_count',
        'is_duplicate',
        'duplicate_of',
        'created_by',
    ];

    protected $casts = [
        'tags'              => 'array',
        'is_duplicate'      => 'boolean',
        'score'             => 'integer',
        'budget_min'        => 'integer',
        'budget_max'        => 'integer',
        'follow_up_count'   => 'integer',
        'site_visit_count'  => 'integer',
        'lead_date'         => 'date',
        'assigned_at'       => 'datetime',
        'accepted_at'       => 'datetime',
        'sla_expires_at'    => 'datetime',
        'last_contacted_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
    ];

    // Valid enum values — easy to extend without migrations
    public const STATUSES = [
        'new', 'contacted', 'qualified', 'site_visit',
        'negotiation', 'closed_won', 'closed_lost', 'on_hold',
    ];

    public const SOURCES = [
        'magicbricks', '99acres', 'housing', 'meta_ads', 'google_ads',
        'website', 'whatsapp', 'facebook', 'instagram',
        'referral', 'walk_in', 'property_portal', 'other',
    ];

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public const SERVICE_TYPES = ['new_project', 'resale', 'rental'];

    public const PROPERTY_TYPES = [
        'apartment', 'villa', 'plot', 'commercial',
        'penthouse', 'studio', 'duplex',
    ];

    // ---------- Relationships ----------

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function siteVisits(): HasMany
    {
        return $this->hasMany(SiteVisit::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    // ---------- Scopes ----------

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCreatedToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}
