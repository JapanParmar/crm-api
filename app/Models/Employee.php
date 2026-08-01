<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'department',
        'designation',
        'employment_type',
        'status',
        'joining_date',
        'salary',
        'pan_number',
        'aadhar_number',
        'emergency_contact_name',
        'emergency_contact_phone',
        'address',
        'bank_name',
        'account_number',
        'ifsc_code',
        'notes',
        'profile_image',
        'work_latitude',
        'work_longitude',
        'hra',
        'allowances',
        'deductions',
    ];

    protected $casts = [
        'salary'       => 'float',
        'joining_date' => 'date',
        'hra'          => 'float',
        'allowances'   => 'float',
        'deductions'   => 'float',
    ];

    public const DEPARTMENTS = [
        'Sales', 'Marketing', 'HR', 'IT', 'Finance', 'Operations', 'Construction', 'Legal',
    ];

    public const EMPLOYMENT_TYPES = [
        'full_time', 'part_time', 'contract', 'intern', 'probation',
    ];

    public const STATUSES = [
        'active', 'on_leave', 'suspended', 'terminated',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
