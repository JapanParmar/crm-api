<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'payment_type',
        'amount',
        'due_date',
        'paid_date',
        'payment_status',
        'receipt_url',
        'notes',
    ];

    protected $casts = [
        'amount'   => 'float',
        'due_date' => 'date',
        'paid_date'=> 'date',
    ];

    public const PAYMENT_TYPES = ['booking', 'installment', 'final', 'registration'];
    public const PAYMENT_STATUSES = ['pending', 'paid', 'overdue'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
