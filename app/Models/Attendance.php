<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_id',
        'date',
        'clock_in',
        'clock_out',
        'work_hours',
        'status',
        'notes',
        'ip_address',
    ];

    protected $casts = [
        'date' => 'date',
        'work_hours' => 'float',
    ];

    public const STATUSES = ['present', 'late', 'half_day', 'absent'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
