<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unit_id',
        'lead_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'assigned_to',
        'booking_date',
        'booking_amount',
        'agreement_status',
        'notes',
    ];

    protected $casts = [
        'booking_date'   => 'date',
        'booking_amount' => 'float',
    ];

    public const AGREEMENT_STATUSES = ['draft', 'signed', 'registered'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
