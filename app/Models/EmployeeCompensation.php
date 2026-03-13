<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCompensation extends Model
{
    protected $fillable = [
        'employee_id',
        'salary_type',
        'base_salary',
        'pay_frequency',
        'effective_date',
        'notes',
    ];

    protected $table = 'employee_compensations';

    protected $casts = [
        'base_salary' => 'decimal:2',
        'effective_date' => 'date:Y-m-d',
    ];

    public static function salaryTypes(): array
    {
        return ['monthly', 'daily', 'hourly'];
    }

    public static function payFrequencies(): array
    {
        return ['monthly', 'semi-monthly', 'weekly'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}