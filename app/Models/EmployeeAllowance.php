<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAllowance extends Model
{
    protected $fillable = [
        'employee_id',
        'allowance_name',
        'amount',
        'frequency',
        'taxable',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'taxable' => 'boolean',
    ];

    public static function frequencies(): array
    {
        return ['monthly', 'per_payroll'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}