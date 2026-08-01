<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tower_id',
        'project_id',
        'unit_number',
        'floor_number',
        'bhk_type',
        'carpet_area',
        'built_up_area',
        'super_built_up_area',
        'facing',
        'base_price',
        'price_per_sqft',
        'floor_rise_charges',
        'plc_charges',
        'parking_charges',
        'club_house_charges',
        'gst_amount',
        'total_price',
        'status',
    ];

    protected $casts = [
        'floor_number'        => 'integer',
        'carpet_area'         => 'float',
        'built_up_area'       => 'float',
        'super_built_up_area' => 'float',
        'base_price'          => 'float',
        'price_per_sqft'      => 'float',
        'floor_rise_charges'  => 'float',
        'plc_charges'         => 'float',
        'parking_charges'     => 'float',
        'club_house_charges'  => 'float',
        'gst_amount'          => 'float',
        'total_price'         => 'float',
    ];

    public const STATUSES = [
        'available', 'reserved', 'hold', 'booked', 'sold', 'cancelled', 'blocked',
    ];

    public const BHK_TYPES = [
        '1BHK', '2BHK', '3BHK', '4BHK', 'Penthouse', 'Commercial', 'Plot',
    ];

    public const FACINGS = [
        'North', 'South', 'East', 'West', 'NE', 'NW', 'SE', 'SW',
    ];

    public function tower(): BelongsTo
    {
        return $this->belongsTo(Tower::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
