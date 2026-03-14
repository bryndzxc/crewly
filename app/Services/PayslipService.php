<?php

namespace App\Services;

use App\Models\CashAdvanceDeduction;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeCompensation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PayslipService extends Service
{
    /**
     * @return array{
     *   company: array{name: string},
     *   employee: array{employee_id: int, name: string, position_title: string},
     *   period: array{from: string, to: string},
     *   earnings: array{base_salary: float, allowances_total: float, allowances: array<int, array{name: string, amount: float, frequency: string}>, other_earnings: float},
     *   deductions: array{cash_advances: float, other_deductions: float},
     *   totals: array{gross_pay: float, total_earnings: float, total_deductions: float}
     * }
     */
    public function build(Employee $employee, Carbon $from, Carbon $to, ?User $requestedBy = null): array
    {
        $employeeId = (int) $employee->employee_id;

        $companyName = '';
        if ($requestedBy?->relationLoaded('company')) {
            $companyName = (string) ($requestedBy->company?->name ?? '');
        }
        if ($companyName === '' && $requestedBy?->company_id) {
            $companyName = (string) (Company::query()->find((int) $requestedBy->company_id)?->name ?? '');
        }

        $name = collect([
            $employee->first_name,
            $employee->middle_name,
            $employee->last_name,
            $employee->suffix,
        ])->map(fn ($v) => trim((string) $v))->filter()->implode(' ');

        $comp = EmployeeCompensation::query()
            ->where('employee_id', $employeeId)
            ->first(['base_salary']);

        $baseSalary = $comp ? (float) ($comp->base_salary ?? 0) : (float) ($employee->monthly_rate ?? 0);

        $allowances = EmployeeAllowance::query()
            ->where('employee_id', $employeeId)
            ->orderBy('allowance_name')
            ->orderBy('id')
            ->get(['allowance_name', 'amount', 'frequency']);

        $allowanceItems = $allowances->map(fn (EmployeeAllowance $a) => [
            'name' => (string) $a->allowance_name,
            'amount' => (float) ($a->amount ?? 0),
            'frequency' => (string) ($a->frequency ?? ''),
        ])->values()->all();

        $allowancesTotal = (float) $allowances->sum(fn (EmployeeAllowance $a) => (float) ($a->amount ?? 0));

        $fromDate = $from->copy()->startOfDay()->toDateString();
        $toDate = $to->copy()->startOfDay()->toDateString();

        $cashAdvanceDeductions = (float) CashAdvanceDeduction::query()
            ->from('cash_advance_deductions')
            ->join('cash_advances', 'cash_advances.id', '=', 'cash_advance_deductions.cash_advance_id')
            ->whereColumn('cash_advances.company_id', 'cash_advance_deductions.company_id')
            ->where('cash_advances.employee_id', $employeeId)
            ->whereBetween('cash_advance_deductions.deducted_at', [$fromDate, $toDate])
            ->sum('cash_advance_deductions.amount');

        $otherEarnings = 0.0;
        $otherDeductions = 0.0;

        $totalEarnings = $baseSalary + $allowancesTotal + $otherEarnings;
        $totalDeductions = $cashAdvanceDeductions + $otherDeductions;
        $grossPay = $totalEarnings - $totalDeductions;

        return [
            'company' => [
                'name' => $companyName,
            ],
            'employee' => [
                'employee_id' => $employeeId,
                'name' => $name,
                'position_title' => (string) ($employee->position_title ?? ''),
            ],
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'earnings' => [
                'base_salary' => $baseSalary,
                'allowances_total' => $allowancesTotal,
                'allowances' => $allowanceItems,
                'other_earnings' => $otherEarnings,
            ],
            'deductions' => [
                'cash_advances' => $cashAdvanceDeductions,
                'other_deductions' => $otherDeductions,
            ],
            'totals' => [
                'gross_pay' => $grossPay,
                'total_earnings' => $totalEarnings,
                'total_deductions' => $totalDeductions,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function buildPayrollExportRows(Carbon $from, Carbon $to, User $user): array
    {
        /** @var Collection<int, Employee> $employees */
        $employees = Employee::query()
            ->orderBy('employee_code', 'asc')
            ->get([
                'employee_id',
                'employee_code',
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'position_title',
                'monthly_rate',
            ]);

        $employeeIds = $employees->pluck('employee_id')->map(fn ($v) => (int) $v)->values()->all();

        $compMap = EmployeeCompensation::query()
            ->whereIn('employee_id', $employeeIds)
            ->get(['employee_id', 'base_salary'])
            ->keyBy('employee_id');

        $allowanceSumMap = EmployeeAllowance::query()
            ->whereIn('employee_id', $employeeIds)
            ->selectRaw('employee_id, SUM(amount) as total_amount')
            ->groupBy('employee_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->employee_id => (float) ($row->total_amount ?? 0)]);

        $fromDate = $from->copy()->startOfDay()->toDateString();
        $toDate = $to->copy()->startOfDay()->toDateString();

        $cashDeductionMap = CashAdvanceDeduction::query()
            ->from('cash_advance_deductions')
            ->join('cash_advances', 'cash_advances.id', '=', 'cash_advance_deductions.cash_advance_id')
            ->whereColumn('cash_advances.company_id', 'cash_advance_deductions.company_id')
            ->whereIn('cash_advances.employee_id', $employeeIds)
            ->whereBetween('cash_advance_deductions.deducted_at', [$fromDate, $toDate])
            ->selectRaw('cash_advances.employee_id as employee_id, SUM(cash_advance_deductions.amount) as total_amount')
            ->groupBy('cash_advances.employee_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->employee_id => (float) ($row->total_amount ?? 0)]);

        $rows = [];

        foreach ($employees as $employee) {
            $employeeId = (int) $employee->employee_id;

            $name = collect([
                $employee->first_name,
                $employee->middle_name,
                $employee->last_name,
                $employee->suffix,
            ])->map(fn ($v) => trim((string) $v))->filter()->implode(' ');

            $comp = $compMap->get($employeeId);
            $baseSalary = $comp ? (float) ($comp->base_salary ?? 0) : (float) ($employee->monthly_rate ?? 0);
            $allowances = (float) ($allowanceSumMap[$employeeId] ?? 0);
            $cashAdvance = (float) ($cashDeductionMap[$employeeId] ?? 0);

            $estimatedGross = ($baseSalary + $allowances) - $cashAdvance;

            $rows[] = [
                'employee_id' => $employeeId,
                'employee_name' => $name,
                'base_salary' => $baseSalary,
                'allowances' => $allowances,
                'cash_advance' => $cashAdvance,
                'estimated_gross' => $estimatedGross,
            ];
        }

        return $rows;
    }
}
