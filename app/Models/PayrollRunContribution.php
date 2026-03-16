<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRunContribution extends Model
{
    protected $fillable = [
        'employee_id',
        'payroll_period_start',
        'payroll_period_end',
        'base_salary',

        'sss_employee',
        'sss_employer',
        'philhealth_employee',
        'philhealth_employer',
        'pagibig_employee',
        'pagibig_employer',

        'sss_employee_computed',
        'sss_employer_computed',
        'philhealth_employee_computed',
        'philhealth_employer_computed',
        'pagibig_employee_computed',
        'pagibig_employer_computed',

        'sss_overridden',
        'philhealth_overridden',
        'pagibig_overridden',
        'override_notes',

        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'employee_id' => 'integer',
        'payroll_period_start' => 'date:Y-m-d',
        'payroll_period_end' => 'date:Y-m-d',
        'base_salary' => 'decimal:2',

        'sss_employee' => 'decimal:2',
        'sss_employer' => 'decimal:2',
        'philhealth_employee' => 'decimal:2',
        'philhealth_employer' => 'decimal:2',
        'pagibig_employee' => 'decimal:2',
        'pagibig_employer' => 'decimal:2',

        'sss_employee_computed' => 'decimal:2',
        'sss_employer_computed' => 'decimal:2',
        'philhealth_employee_computed' => 'decimal:2',
        'philhealth_employer_computed' => 'decimal:2',
        'pagibig_employee_computed' => 'decimal:2',
        'pagibig_employer_computed' => 'decimal:2',

        'sss_overridden' => 'boolean',
        'philhealth_overridden' => 'boolean',
        'pagibig_overridden' => 'boolean',

        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }
}
