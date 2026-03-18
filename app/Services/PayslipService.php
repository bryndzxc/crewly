<?php

namespace App\Services;

use App\Exceptions\GovernmentContributionConfigMissingException;
use App\Models\CashAdvanceDeduction;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeCompensation;
use App\Models\PayrollRunItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PayslipService extends Service
{
    public function __construct(
        private readonly PayrollRunContributionService $payrollRunContributionService,
    ) {}

    /**
     * @return array{
     *   company: array{name: string},
     *   employee: array{employee_id: int, name: string, position_title: string},
     *   period: array{from: string, to: string},
     *   earnings: array{base_salary: float, allowances_total: float, allowances: array<int, array{name: string, amount: float, frequency: string}>, other_earnings: float},
      *   deductions: array{sss: float, philhealth: float, pagibig: float, government_total: float, cash_advances: float, tax: float, other_deductions: float},
      *   totals: array{gross_pay: float, net_pay: float, total_earnings: float, total_deductions: float}
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

        /** @var PayrollRunItem|null $runItem */
        $runItem = PayrollRunItem::query()
            ->join('payroll_runs', 'payroll_runs.id', '=', 'payroll_run_items.payroll_run_id')
            ->where('payroll_run_items.employee_id', $employeeId)
            ->where('payroll_runs.company_id', (int) ($employee->company_id ?? 0))
            ->whereDate('payroll_runs.period_start', $fromDate)
            ->whereDate('payroll_runs.period_end', $toDate)
            ->orderByDesc('payroll_runs.id')
            ->first(['payroll_run_items.*']);

        // Prefer stored payroll run items for correctness/locking.
        if ($runItem) {
            $baseSalary = (float) ($runItem->basic_pay ?? $baseSalary);
            $allowancesTotal = (float) ($runItem->allowances_total ?? $allowancesTotal);

            $otherEarnings = (float) ($runItem->other_earnings ?? 0);
            $grossPay = (float) ($runItem->gross_pay ?? ($baseSalary + $allowancesTotal + $otherEarnings));

            $sssEmployee = (float) ($runItem->sss_employee ?? 0);
            $philhealthEmployee = (float) ($runItem->philhealth_employee ?? 0);
            $pagibigEmployee = (float) ($runItem->pagibig_employee ?? 0);
            $governmentEmployeeTotal = $sssEmployee + $philhealthEmployee + $pagibigEmployee;

            $cashAdvanceDeductions = (float) ($runItem->cash_advance_deduction ?? 0);
            $taxDeduction = (float) ($runItem->tax_deduction ?? 0);
            $otherDeductions = (float) ($runItem->other_deductions ?? 0);

            $totalEarnings = $grossPay;
            $totalDeductions = (float) ($runItem->total_deductions ?? ($governmentEmployeeTotal + $cashAdvanceDeductions + $taxDeduction + $otherDeductions));
            $netPay = (float) ($runItem->net_pay ?? ($grossPay - $totalDeductions));
        } else {
            $cashAdvanceDeductions = (float) CashAdvanceDeduction::query()
                ->from('cash_advance_deductions')
                ->join('cash_advances', 'cash_advances.id', '=', 'cash_advance_deductions.cash_advance_id')
                ->whereColumn('cash_advances.company_id', 'cash_advance_deductions.company_id')
                ->where('cash_advances.employee_id', $employeeId)
                ->whereBetween('cash_advance_deductions.deducted_at', [$fromDate, $toDate])
                ->sum('cash_advance_deductions.amount');

            $sssEmployee = 0.0;
            $philhealthEmployee = 0.0;
            $pagibigEmployee = 0.0;

            try {
                $contribMap = $this->payrollRunContributionService->upsertForPeriod(
                    employeeIds: [$employeeId],
                    periodStart: $from,
                    periodEnd: $to,
                    baseSalaryByEmployeeId: [$employeeId => $baseSalary],
                    actorUserId: (int) ($requestedBy?->id ?? 0) ?: null,
                );

                $contrib = $contribMap->get($employeeId);
                $sssEmployee = $contrib ? (float) ($contrib->sss_employee ?? 0) : 0.0;
                $philhealthEmployee = $contrib ? (float) ($contrib->philhealth_employee ?? 0) : 0.0;
                $pagibigEmployee = $contrib ? (float) ($contrib->pagibig_employee ?? 0) : 0.0;
            } catch (GovernmentContributionConfigMissingException) {
                // Keep payslip usable; show zero contributions until settings are configured.
            }

            $governmentEmployeeTotal = $sssEmployee + $philhealthEmployee + $pagibigEmployee;

            $otherEarnings = 0.0;
            $taxDeduction = 0.0;
            $otherDeductions = 0.0;

            $grossPay = $baseSalary + $allowancesTotal + $otherEarnings;
            $totalEarnings = $grossPay;
            $totalDeductions = $governmentEmployeeTotal + $cashAdvanceDeductions + $taxDeduction + $otherDeductions;
            $netPay = $grossPay - $totalDeductions;
        }

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
                'sss' => $sssEmployee,
                'philhealth' => $philhealthEmployee,
                'pagibig' => $pagibigEmployee,
                'government_total' => $governmentEmployeeTotal,
                'cash_advances' => $cashAdvanceDeductions,
                'tax' => $taxDeduction,
                'other_deductions' => $otherDeductions,
            ],
            'totals' => [
                'gross_pay' => $grossPay,
                'total_earnings' => $totalEarnings,
                'total_deductions' => $totalDeductions,
                'net_pay' => $netPay,
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

        $baseSalaryByEmployeeId = [];
        foreach ($employees as $employee) {
            $employeeId = (int) $employee->employee_id;
            $comp = $compMap->get($employeeId);
            $baseSalaryByEmployeeId[$employeeId] = $comp
                ? (float) ($comp->base_salary ?? 0)
                : (float) ($employee->monthly_rate ?? 0);
        }

        try {
            $contribMap = $this->payrollRunContributionService->upsertForPeriod(
                employeeIds: $employeeIds,
                periodStart: $from,
                periodEnd: $to,
                baseSalaryByEmployeeId: $baseSalaryByEmployeeId,
                actorUserId: (int) ($user->id ?? 0) ?: null,
            );
        } catch (GovernmentContributionConfigMissingException) {
            $contribMap = collect();
        }

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

            $contrib = $contribMap->get($employeeId);
            $sssEmployee = $contrib ? (float) ($contrib->sss_employee ?? 0) : 0.0;
            $philhealthEmployee = $contrib ? (float) ($contrib->philhealth_employee ?? 0) : 0.0;
            $pagibigEmployee = $contrib ? (float) ($contrib->pagibig_employee ?? 0) : 0.0;
            $governmentEmployeeTotal = $sssEmployee + $philhealthEmployee + $pagibigEmployee;

            $estimatedGross = ($baseSalary + $allowances) - $cashAdvance;
            $estimatedNet = $estimatedGross - $governmentEmployeeTotal;

            $rows[] = [
                'employee_id' => $employeeId,
                'employee_name' => $name,
                'base_salary' => $baseSalary,
                'allowances' => $allowances,
                'cash_advance' => $cashAdvance,
                'sss_employee' => $sssEmployee,
                'philhealth_employee' => $philhealthEmployee,
                'pagibig_employee' => $pagibigEmployee,
                'government_contributions_employee_total' => $governmentEmployeeTotal,
                'estimated_gross' => $estimatedGross,
                'estimated_net' => $estimatedNet,
            ];
        }

        return $rows;
    }
}
