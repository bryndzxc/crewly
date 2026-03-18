<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRunItem extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'basic_pay',
        'allowances_total',
        'other_earnings',
        'gross_pay',
        'sss_employee',
        'philhealth_employee',
        'pagibig_employee',
        'cash_advance_deduction',
        'other_deductions',
        'tax_deduction',
        'total_deductions',
        'net_pay',
        'tax_overridden',
        'deduction_notes',
    ];

    protected $casts = [
        'payroll_run_id' => 'integer',
        'employee_id' => 'integer',
        'basic_pay' => 'decimal:2',
        'allowances_total' => 'decimal:2',
        'other_earnings' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'sss_employee' => 'decimal:2',
        'philhealth_employee' => 'decimal:2',
        'pagibig_employee' => 'decimal:2',
        'cash_advance_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'tax_deduction' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'tax_overridden' => 'boolean',
    ];

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
