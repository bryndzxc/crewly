<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeCompensation;
use App\Models\EmployeeSalaryHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeePayrollService extends Service
{
    public function createCompensation(
        Employee $employee,
        array $attributes,
        ?int $approvedByUserId = null
    ): EmployeeCompensation
    {
        return DB::transaction(function () use ($employee, $attributes, $approvedByUserId) {
            $existing = EmployeeCompensation::query()
                ->where('employee_id', (int) $employee->employee_id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'base_salary' => 'This employee already has an active compensation record.',
                ]);
            }

            $compensation = EmployeeCompensation::query()->create($this->compensationPayload($employee, $attributes));

            $newSalary = $this->normalizeMoney($compensation->base_salary);

            if ($newSalary !== '0.00') {
                EmployeeSalaryHistory::query()->create([
                    'employee_id' => (int) $employee->employee_id,
                    'previous_salary' => '0.00',
                    'new_salary' => $newSalary,
                    'effective_date' => $compensation->effective_date,
                    'reason' => $attributes['change_reason'] ?? 'Initial compensation recorded.',
                    'approved_by' => $approvedByUserId,
                ]);
            }

            return $compensation;
        });
    }

    public function updateCompensation(
        Employee $employee,
        EmployeeCompensation $compensation,
        array $attributes,
        ?int $approvedByUserId = null
    ): EmployeeCompensation {
        return DB::transaction(function () use ($employee, $compensation, $attributes, $approvedByUserId) {
            $previousSalary = $this->normalizeMoney($compensation->base_salary);

            $compensation->fill($this->compensationPayload($employee, $attributes));
            $compensation->save();

            $newSalary = $this->normalizeMoney($compensation->base_salary);

            if ($previousSalary !== $newSalary) {
                EmployeeSalaryHistory::query()->create([
                    'employee_id' => (int) $employee->employee_id,
                    'previous_salary' => $previousSalary,
                    'new_salary' => $newSalary,
                    'effective_date' => $compensation->effective_date,
                    'reason' => $attributes['change_reason'] ?? null,
                    'approved_by' => $approvedByUserId,
                ]);
            }

            return $compensation->fresh();
        });
    }

    public function createAllowance(Employee $employee, array $attributes): EmployeeAllowance
    {
        return EmployeeAllowance::query()->create($this->allowancePayload($employee, $attributes));
    }

    public function updateAllowance(Employee $employee, EmployeeAllowance $allowance, array $attributes): EmployeeAllowance
    {
        $allowance->fill($this->allowancePayload($employee, $attributes));
        $allowance->save();

        return $allowance->fresh();
    }

    public function deleteAllowance(EmployeeAllowance $allowance): void
    {
        $allowance->delete();
    }

    private function compensationPayload(Employee $employee, array $attributes): array
    {
        return [
            'employee_id' => (int) $employee->employee_id,
            'salary_type' => $attributes['salary_type'],
            'base_salary' => $attributes['base_salary'],
            'pay_frequency' => $attributes['pay_frequency'],
            'effective_date' => $attributes['effective_date'],
            'notes' => $attributes['notes'] ?? null,
        ];
    }

    private function allowancePayload(Employee $employee, array $attributes): array
    {
        return [
            'employee_id' => (int) $employee->employee_id,
            'allowance_name' => $attributes['allowance_name'],
            'amount' => $attributes['amount'],
            'frequency' => $attributes['frequency'],
            'taxable' => (bool) ($attributes['taxable'] ?? false),
        ];
    }

    private function normalizeMoney(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}