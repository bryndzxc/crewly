<?php

namespace App\Services;

use App\Exceptions\GovernmentContributionConfigMissingException;
use App\Models\Employee;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeCompensation;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayrollRunService extends Service
{
    public function __construct(
        private readonly PayrollRunContributionService $payrollRunContributionService,
    ) {}

    public function inferPayFrequency(Carbon $from, Carbon $to): string
    {
        $days = $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;

        if ($days <= 8) {
            return 'weekly';
        }

        if ($days <= 16) {
            return 'semi-monthly';
        }

        return 'monthly';
    }

    public function findRun(int $companyId, Carbon $from, Carbon $to, string $payFrequency): ?PayrollRun
    {
        return PayrollRun::query()
            ->where('company_id', $companyId)
            ->whereDate('period_start', $from->toDateString())
            ->whereDate('period_end', $to->toDateString())
            ->where('pay_frequency', $payFrequency)
            ->first();
    }

    public function generateOrRegenerate(int $companyId, Carbon $from, Carbon $to, string $payFrequency, ?int $actorUserId): PayrollRun
    {
        return DB::transaction(function () use ($companyId, $from, $to, $payFrequency, $actorUserId) {
            /** @var PayrollRun|null $run */
            $run = PayrollRun::query()
                ->where('company_id', $companyId)
                ->whereDate('period_start', $from->toDateString())
                ->whereDate('period_end', $to->toDateString())
                ->where('pay_frequency', $payFrequency)
                ->lockForUpdate()
                ->first();

            if (!$run) {
                $run = PayrollRun::query()->create([
                    'company_id' => $companyId,
                    'period_start' => $from->toDateString(),
                    'period_end' => $to->toDateString(),
                    'pay_frequency' => $payFrequency,
                    'status' => PayrollRun::STATUS_DRAFT,
                    'generated_by' => $actorUserId,
                ]);
            }

            if ($run->isFinalized() || $run->isReleased()) {
                throw new RuntimeException('This payroll run is locked and can no longer be regenerated.');
            }

            if ($actorUserId) {
                $run->forceFill(['generated_by' => $actorUserId])->save();
            }

            $this->computeAndUpsertItems($run, $actorUserId);

            return $run->refresh();
        });
    }

    public function computeAndUpsertItems(PayrollRun $run, ?int $actorUserId): void
    {
        $from = Carbon::parse((string) $run->period_start)->startOfDay();
        $to = Carbon::parse((string) $run->period_end)->startOfDay();

        /** @var Collection<int, Employee> $employees */
        $employees = Employee::query()
            ->where('status', '!=', 'Terminated')
            ->orderBy('employee_code', 'asc')
            ->get([
                'employee_id',
                'employee_code',
                'department_id',
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

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();

        $cashDeductionMap = DB::table('cash_advance_deductions')
            ->join('cash_advances', 'cash_advances.id', '=', 'cash_advance_deductions.cash_advance_id')
            ->whereColumn('cash_advances.company_id', 'cash_advance_deductions.company_id')
            ->whereBetween('cash_advance_deductions.deducted_at', [$fromDate, $toDate])
            ->whereIn('cash_advances.employee_id', $employeeIds)
            ->selectRaw('cash_advances.employee_id as employee_id, SUM(cash_advance_deductions.amount) as total_amount')
            ->groupBy('cash_advances.employee_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->employee_id => (float) ($row->total_amount ?? 0)]);

        $basicPayByEmployeeId = [];
        foreach ($employees as $employee) {
            $employeeId = (int) $employee->employee_id;
            $comp = $compMap->get($employeeId);
            $basicPayByEmployeeId[$employeeId] = $comp
                ? (float) ($comp->base_salary ?? 0)
                : (float) ($employee->monthly_rate ?? 0);
        }

        $contribMap = collect();
        try {
            $contribMap = $this->payrollRunContributionService->upsertForPeriod(
                employeeIds: $employeeIds,
                periodStart: $from,
                periodEnd: $to,
                baseSalaryByEmployeeId: $basicPayByEmployeeId,
                actorUserId: $actorUserId,
            );
        } catch (GovernmentContributionConfigMissingException) {
            $contribMap = collect();
        }

        /** @var Collection<int, PayrollRunItem> $existing */
        $existing = PayrollRunItem::query()
            ->where('payroll_run_id', (int) $run->id)
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->keyBy(fn (PayrollRunItem $i) => (int) $i->employee_id);

        foreach ($employees as $employee) {
            $employeeId = (int) $employee->employee_id;

            $basicPay = (float) ($basicPayByEmployeeId[$employeeId] ?? 0);
            $allowancesTotal = (float) ($allowanceSumMap[$employeeId] ?? 0);
            $otherEarnings = 0.0;

            $grossPay = $basicPay + $allowancesTotal + $otherEarnings;

            $contrib = $contribMap->get($employeeId);
            $sssEmployee = $contrib ? (float) ($contrib->sss_employee ?? 0) : 0.0;
            $philhealthEmployee = $contrib ? (float) ($contrib->philhealth_employee ?? 0) : 0.0;
            $pagibigEmployee = $contrib ? (float) ($contrib->pagibig_employee ?? 0) : 0.0;

            $cashAdvance = (float) ($cashDeductionMap[$employeeId] ?? 0);

            $row = $existing->get($employeeId);

            $taxOverridden = (bool) ($row?->tax_overridden ?? false);
            $taxDeduction = $taxOverridden ? (float) ($row?->tax_deduction ?? 0) : 0.0;

            $otherDeductions = (float) ($row?->other_deductions ?? 0);
            $deductionNotes = $row?->deduction_notes;

            $totalDeductions = $sssEmployee + $philhealthEmployee + $pagibigEmployee + $cashAdvance + $otherDeductions + $taxDeduction;
            $netPay = $grossPay - $totalDeductions;

            $payload = [
                'basic_pay' => $basicPay,
                'allowances_total' => $allowancesTotal,
                'other_earnings' => $otherEarnings,
                'gross_pay' => $grossPay,

                'sss_employee' => $sssEmployee,
                'philhealth_employee' => $philhealthEmployee,
                'pagibig_employee' => $pagibigEmployee,

                'cash_advance_deduction' => $cashAdvance,
                'other_deductions' => $otherDeductions,
                'tax_deduction' => $taxDeduction,

                'total_deductions' => $totalDeductions,
                'net_pay' => $netPay,

                'tax_overridden' => $taxOverridden,
                'deduction_notes' => $deductionNotes,
            ];

            if (!$row) {
                PayrollRunItem::query()->create([
                    'payroll_run_id' => (int) $run->id,
                    'employee_id' => $employeeId,
                    ...$payload,
                ]);

                continue;
            }

            $row->forceFill($payload)->save();
        }
    }

    /**
     * @return array{run: array<string, mixed>|null, rows: array<int, array<string, mixed>>, totals: array<string, mixed>}
     */
    public function buildRegisterPayload(?PayrollRun $run): array
    {
        if (!$run) {
            return [
                'run' => null,
                'rows' => [],
                'totals' => [
                    'employees' => 0,
                    'total_gross_pay' => 0,
                    'total_deductions' => 0,
                    'total_net_pay' => 0,
                ],
            ];
        }

        $run->loadMissing(['generatedByUser']);

        // IMPORTANT: Employee name/title fields are encrypted at rest.
        // If we join and select them as raw columns, Laravel will not decrypt them.
        // Load the Employee model (with casts) to ensure decrypted values for the UI.
        $items = PayrollRunItem::query()
            ->where('payroll_run_id', (int) $run->id)
            ->with([
                'employee.department',
            ])
            ->get()
            ->sortBy(fn (PayrollRunItem $i) => (string) ($i->employee?->employee_code ?? ''))
            ->values();

        $rows = [];
        $grossTotal = 0.0;
        $deductionsTotal = 0.0;
        $netTotal = 0.0;

        foreach ($items as $r) {
            $employee = $r->employee;

            $name = collect([
                $employee?->first_name,
                $employee?->middle_name,
                $employee?->last_name,
                $employee?->suffix,
            ])->map(fn ($v) => trim((string) $v))->filter()->implode(' ');

            $grossTotal += (float) ($r->gross_pay ?? 0);
            $deductionsTotal += (float) ($r->total_deductions ?? 0);
            $netTotal += (float) ($r->net_pay ?? 0);

            $rows[] = [
                'id' => (int) ($r->id ?? 0),
                'employee_id' => (int) ($r->employee_id ?? 0),
                'employee_code' => (string) ($employee?->employee_code ?? ''),
                'employee_name' => $name,
                'department' => (string) ($employee?->department?->name ?? ''),
                'position_title' => (string) ($employee?->position_title ?? ''),

                'basic_pay' => (float) ($r->basic_pay ?? 0),
                'allowances_total' => (float) ($r->allowances_total ?? 0),
                'other_earnings' => (float) ($r->other_earnings ?? 0),
                'gross_pay' => (float) ($r->gross_pay ?? 0),

                'sss_employee' => (float) ($r->sss_employee ?? 0),
                'philhealth_employee' => (float) ($r->philhealth_employee ?? 0),
                'pagibig_employee' => (float) ($r->pagibig_employee ?? 0),

                'tax_deduction' => (float) ($r->tax_deduction ?? 0),
                'cash_advance_deduction' => (float) ($r->cash_advance_deduction ?? 0),
                'other_deductions' => (float) ($r->other_deductions ?? 0),

                'total_deductions' => (float) ($r->total_deductions ?? 0),
                'net_pay' => (float) ($r->net_pay ?? 0),

                'tax_overridden' => (bool) ($r->tax_overridden ?? false),
                'deduction_notes' => $r->deduction_notes,
            ];
        }

        return [
            'run' => [
                'id' => (int) $run->id,
                'status' => (string) $run->status,
                'pay_frequency' => (string) $run->pay_frequency,
                'period_start' => $run->period_start?->toDateString(),
                'period_end' => $run->period_end?->toDateString(),
                'generated_at' => $run->created_at?->format('Y-m-d H:i:s'),
                'generated_by' => $run->generatedByUser ? (string) ($run->generatedByUser->name ?? '') : null,
            ],
            'rows' => $rows,
            'totals' => [
                'employees' => count($rows),
                'total_gross_pay' => $grossTotal,
                'total_deductions' => $deductionsTotal,
                'total_net_pay' => $netTotal,
            ],
        ];
    }

    public function updateManualDeductions(PayrollRunItem $item, float $taxDeduction, float $otherDeductions, ?string $notes): PayrollRunItem
    {
        $item->loadMissing('payrollRun');

        $run = $item->payrollRun;
        if (!$run) {
            throw new RuntimeException('Payroll run not found.');
        }

        if ($run->isFinalized() || $run->isReleased()) {
            throw new RuntimeException('This payroll run is locked and can no longer be edited.');
        }

        $item->forceFill([
            'tax_overridden' => true,
            'tax_deduction' => max(0, $taxDeduction),
            'other_deductions' => max(0, $otherDeductions),
            'deduction_notes' => $notes,
        ]);

        $item->forceFill([
            'total_deductions' => $this->recomputeTotalDeductions($item),
        ]);

        $item->forceFill([
            'net_pay' => ((float) $item->gross_pay) - ((float) $item->total_deductions),
        ]);

        $item->save();

        return $item->refresh();
    }

    private function recomputeTotalDeductions(PayrollRunItem $item): float
    {
        return ((float) $item->sss_employee)
            + ((float) $item->philhealth_employee)
            + ((float) $item->pagibig_employee)
            + ((float) $item->cash_advance_deduction)
            + ((float) $item->other_deductions)
            + ((float) $item->tax_deduction);
    }

    public function transitionStatus(PayrollRun $run, string $toStatus, User $actor): PayrollRun
    {
        $allowed = [
            PayrollRun::STATUS_DRAFT => PayrollRun::STATUS_REVIEWED,
            PayrollRun::STATUS_REVIEWED => PayrollRun::STATUS_FINALIZED,
            PayrollRun::STATUS_FINALIZED => PayrollRun::STATUS_RELEASED,
        ];

        $current = (string) $run->status;
        if (!isset($allowed[$current]) || $allowed[$current] !== $toStatus) {
            throw new RuntimeException('Invalid payroll status transition.');
        }

        if ($toStatus === PayrollRun::STATUS_REVIEWED) {
            $run->forceFill(['status' => PayrollRun::STATUS_REVIEWED]);
            $run->save();
            return $run->refresh();
        }

        if ($toStatus === PayrollRun::STATUS_FINALIZED) {
            $run->forceFill([
                'status' => PayrollRun::STATUS_FINALIZED,
                'finalized_by' => (int) $actor->id,
                'finalized_at' => Carbon::now(),
            ]);
            $run->save();
            return $run->refresh();
        }

        $run->forceFill([
            'status' => PayrollRun::STATUS_RELEASED,
            'released_by' => (int) $actor->id,
            'released_at' => Carbon::now(),
        ]);
        $run->save();

        return $run->refresh();
    }
}
