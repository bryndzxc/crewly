<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryHistory extends Model
{
    protected $fillable = [
        'employee_id',
        'previous_salary',
        'new_salary',
        'effective_date',
        'reason',
        'approved_by',
    ];

    protected $casts = [
        'previous_salary' => 'decimal:2',
        'new_salary' => 'decimal:2',
        'effective_date' => 'date:Y-m-d',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }
}