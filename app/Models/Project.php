<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'status',
        'location',
        'city',
        'developer',
        'budget',
        'total_units',
        'available_units',
        'sold_units',
        'price_min',
        'price_max',
        'launch_date',
        'possession_date',
        'description',
        'amenities',
        'images',
        'manager_id',
        'created_by',
    ];

    protected $casts = [
        'amenities'       => 'array',
        'images'          => 'array',
        'budget'          => 'float',
        'price_min'       => 'float',
        'price_max'       => 'float',
        'total_units'     => 'integer',
        'available_units' => 'integer',
        'sold_units'      => 'integer',
        'launch_date'     => 'date',
        'possession_date' => 'date',
    ];

    public const TYPES = [
        'residential', 'commercial', 'mixed_use', 'industrial', 'plot',
    ];

    public const STATUSES = [
        'planning', 'active', 'under_construction', 'completed', 'on_hold',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function siteVisits(): HasMany
    {
        return $this->hasMany(SiteVisit::class);
    }
}
