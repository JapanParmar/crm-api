<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tower extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'tower_name',
        'total_floors',
        'units_per_floor',
        'has_lift',
        'parking_details',
    ];

    protected $casts = [
        'total_floors'    => 'integer',
        'units_per_floor' => 'integer',
        'has_lift'        => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
}
