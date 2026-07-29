<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'basic_salary',
        'hra',
        'allowances',
        'deductions',
        'net_salary',
        'status',
        'payment_date',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'month'        => 'integer',
        'year'         => 'integer',
        'basic_salary' => 'float',
        'hra'          => 'float',
        'allowances'   => 'float',
        'deductions'   => 'float',
        'net_salary'   => 'float',
        'payment_date' => 'date',
    ];

    public const STATUSES = ['pending', 'processing', 'paid'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
