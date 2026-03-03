<?php

namespace App\Services;

use App\Models\CashAdvance;
use App\Models\CashAdvanceDeduction;
use App\Models\Employee;
use App\Repositories\CashAdvanceRepository;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CashAdvanceService extends Service
{
    public function __construct(
        private readonly CashAdvanceRepository $cashAdvanceRepository,
        private readonly EmployeeResolver $employeeResolver,
        private readonly DocumentCryptoService $crypto,
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function index(Request $request): array
    {
        $status = trim((string) $request->query('status', ''));
        $employeeId = (int) $request->query('employee_id', 0);
        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 10);

        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $now = Carbon::now();
        $summaryYear = (int) $request->query('summary_year', (int) $now->format('Y'));
        $summaryMonth = (int) $request->query('summary_month', (int) $now->format('n'));
        if ($summaryMonth < 1 || $summaryMonth > 12) {
            $summaryMonth = (int) $now->format('n');
        }

        $cashAdvances = $this->cashAdvanceRepository->paginateIndex(
            status: $status,
            employeeId: $employeeId,
            q: $q,
            perPage: $perPage,
        )
            ->through(function (CashAdvance $ca) {
                return [
                    'id' => (int) $ca->id,
                    'employee' => $ca->employee ? $ca->employee->only(['employee_id', 'employee_code', 'first_name', 'middle_name', 'last_name', 'suffix']) : null,
                    'amount' => (float) ($ca->amount ?? 0),
                    'reason' => $ca->reason,
                    'requested_at' => $ca->requested_at?->format('Y-m-d'),
                    'status' => (string) $ca->status,
                    'installment_amount' => $ca->installment_amount !== null ? (float) $ca->installment_amount : null,
                    'installments_count' => $ca->installments_count !== null ? (int) $ca->installments_count : null,
                    'total_deducted' => (float) $ca->total_deducted,
                    'remaining_balance' => (float) $ca->remaining_balance,
                    'approved_at' => $ca->approved_at?->format('Y-m-d H:i:s'),
                    'rejected_at' => $ca->rejected_at?->format('Y-m-d H:i:s'),
                    'completed_at' => $ca->completed_at?->format('Y-m-d H:i:s'),
                ];
            });

        $employees = $this->cashAdvanceRepository->getEmployeesForFilter();

        $monthlyTotals = $this->cashAdvanceRepository->monthlyDeductionTotals($summaryYear, $summaryMonth, $employeeId > 0 ? $employeeId : null);
        $monthlySummary = $this->hydrateMonthlySummary($monthlyTotals);

        return [
            'cashAdvances' => $cashAdvances,
            'employees' => $employees,
            'filters' => [
                'q' => $q,
                'status' => $status,
                'employee_id' => $employeeId ?: null,
                'per_page' => $perPage,
                'summary_year' => $summaryYear,
                'summary_month' => $summaryMonth,
            ],
            'monthlySummary' => $monthlySummary,
        ];
    }

    public function show(Request $request, CashAdvance $cashAdvance): array
    {
        $cashAdvance = $this->cashAdvanceRepository->loadShow($cashAdvance);

        return [
            'cashAdvance' => [
                'id' => (int) $cashAdvance->id,
                'employee_id' => (int) $cashAdvance->employee_id,
                'amount' => (float) ($cashAdvance->amount ?? 0),
                'reason' => $cashAdvance->reason,
                'requested_at' => $cashAdvance->requested_at?->format('Y-m-d'),
                'status' => (string) $cashAdvance->status,
                'decision_remarks' => $cashAdvance->decision_remarks,
                'installment_amount' => $cashAdvance->installment_amount !== null ? (float) $cashAdvance->installment_amount : null,
                'installments_count' => $cashAdvance->installments_count !== null ? (int) $cashAdvance->installments_count : null,
                'total_deducted' => (float) $cashAdvance->total_deducted,
                'remaining_balance' => (float) $cashAdvance->remaining_balance,
                'approved_at' => $cashAdvance->approved_at?->format('Y-m-d H:i:s'),
                'rejected_at' => $cashAdvance->rejected_at?->format('Y-m-d H:i:s'),
                'completed_at' => $cashAdvance->completed_at?->format('Y-m-d H:i:s'),
                'has_attachment' => (bool) $cashAdvance->has_attachment,
                'attachment_original_name' => $cashAdvance->attachment_original_name,
                'attachment_download_url' => $cashAdvance->attachment_download_url,
                'created_at' => $cashAdvance->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $cashAdvance->updated_at?->format('Y-m-d H:i:s'),
            ],
            'employee' => $cashAdvance->employee ? $cashAdvance->employee->only(['employee_id', 'employee_code', 'first_name', 'middle_name', 'last_name', 'suffix']) : null,
            'actors' => [
                'requested_by' => $cashAdvance->requestedBy ? $cashAdvance->requestedBy->only(['id', 'name', 'role']) : null,
                'approved_by' => $cashAdvance->approvedBy ? $cashAdvance->approvedBy->only(['id', 'name', 'role']) : null,
                'rejected_by' => $cashAdvance->rejectedBy ? $cashAdvance->rejectedBy->only(['id', 'name', 'role']) : null,
                'completed_by' => $cashAdvance->completedBy ? $cashAdvance->completedBy->only(['id', 'name', 'role']) : null,
            ],
            'deductions' => ($cashAdvance->deductions ?? collect())->map(function (CashAdvanceDeduction $d) {
                return [
                    'id' => (int) $d->id,
                    'deducted_at' => $d->deducted_at?->format('Y-m-d'),
                    'amount' => (float) ($d->amount ?? 0),
                    'notes' => $d->notes,
                    'created_at' => $d->created_at?->format('Y-m-d H:i:s'),
                    'created_by' => $d->createdBy ? $d->createdBy->only(['id', 'name', 'role']) : null,
                ];
            })->values(),
            'actions' => [
                'approve' => $request->user()?->can('approve', $cashAdvance) ?? false,
                'reject' => $request->user()?->can('reject', $cashAdvance) ?? false,
                'addDeduction' => $request->user()?->can('addDeduction', $cashAdvance) ?? false,
                'downloadAttachment' => $request->user()?->can('downloadAttachment', $cashAdvance) ?? false,
            ],
        ];
    }

    public function myIndex(Request $request): array
    {
        $employee = $this->employeeResolver->requireCurrent($request->user());

        $items = CashAdvance::query()
            ->where('employee_id', (int) $employee->employee_id)
            ->withSum('deductions', 'amount')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(function (CashAdvance $ca) {
                return [
                    'id' => (int) $ca->id,
                    'amount' => (float) ($ca->amount ?? 0),
                    'reason' => $ca->reason,
                    'requested_at' => $ca->requested_at?->format('Y-m-d'),
                    'status' => (string) $ca->status,
                    'installment_amount' => $ca->installment_amount !== null ? (float) $ca->installment_amount : null,
                    'installments_count' => $ca->installments_count !== null ? (int) $ca->installments_count : null,
                    'total_deducted' => (float) $ca->total_deducted,
                    'remaining_balance' => (float) $ca->remaining_balance,
                    'has_attachment' => (bool) $ca->has_attachment,
                    'created_at' => $ca->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        return [
            'cashAdvances' => $items,
        ];
    }

    public function createMy(Request $request, array $validated): CashAdvance
    {
        $employee = $this->employeeResolver->requireCurrent($request->user());
        $userId = $request->user()?->id;

        /** @var UploadedFile|null $file */
        $file = $request->file('attachment');

        return DB::transaction(function () use ($employee, $validated, $file, $userId) {
            $cashAdvance = new CashAdvance();
            $cashAdvance->employee_id = (int) $employee->employee_id;
            $cashAdvance->amount = (float) $validated['amount'];
            $cashAdvance->reason = $validated['reason'] ?? null;
            $cashAdvance->requested_at = Carbon::parse((string) $validated['requested_at'])->toDateString();
            $cashAdvance->status = CashAdvance::STATUS_PENDING;
            $cashAdvance->requested_by = $userId;
            $cashAdvance->save();

            if ($file instanceof UploadedFile) {
                $this->storeAttachment($cashAdvance, $file);
            }

            $this->activityLogService->log('requested', $cashAdvance, [
                'employee_id' => (int) $cashAdvance->employee_id,
                'amount' => (float) ($cashAdvance->amount ?? 0),
                'requested_at' => $cashAdvance->requested_at instanceof Carbon ? $cashAdvance->requested_at->format('Y-m-d') : (string) ($cashAdvance->requested_at ?? ''),
                'status' => (string) $cashAdvance->status,
                'has_attachment' => (bool) $cashAdvance->has_attachment,
            ], 'Cash advance requested.');

            app(\App\Services\AuditLogger::class)->log(
                'cash_advance.requested',
                $cashAdvance,
                [],
                [
                    'employee_id' => (int) $cashAdvance->employee_id,
                    'amount' => (float) ($cashAdvance->amount ?? 0),
                    'requested_at' => $cashAdvance->requested_at instanceof Carbon ? $cashAdvance->requested_at->format('Y-m-d') : (string) ($cashAdvance->requested_at ?? ''),
                    'status' => (string) $cashAdvance->status,
                    'has_attachment' => (bool) $cashAdvance->has_attachment,
                ],
                [],
                'Cash advance requested.'
            );

            return $cashAdvance;
        });
    }

    public function approve(CashAdvance $cashAdvance, array $validated, ?int $userId): CashAdvance
    {
        return DB::transaction(function () use ($cashAdvance, $validated, $userId) {
            $fromStatus = (string) $cashAdvance->status;

            if ($cashAdvance->status !== CashAdvance::STATUS_PENDING) {
                throw new RuntimeException('Only pending cash advances can be approved.');
            }

            $cashAdvance->status = CashAdvance::STATUS_APPROVED;
            $cashAdvance->approved_by = $userId;
            $cashAdvance->approved_at = isset($validated['approved_at']) && (string) $validated['approved_at'] !== ''
                ? Carbon::parse((string) $validated['approved_at'])
                : Carbon::now();

            $cashAdvance->rejected_by = null;
            $cashAdvance->rejected_at = null;

            $cashAdvance->decision_remarks = $validated['decision_remarks'] ?? null;
            $cashAdvance->installment_amount = (float) $validated['installment_amount'];
            $cashAdvance->installments_count = (int) $validated['installments_count'];
            $cashAdvance->save();

            $this->activityLogService->log('approved', $cashAdvance, [
                'from_status' => $fromStatus,
                'to_status' => (string) $cashAdvance->status,
                'installment_amount' => (float) ($cashAdvance->installment_amount ?? 0),
                'installments_count' => (int) ($cashAdvance->installments_count ?? 0),
            ], 'Cash advance approved.');

            app(\App\Services\AuditLogger::class)->log(
                'cash_advance.approved',
                $cashAdvance,
                ['status' => $fromStatus],
                [
                    'status' => (string) $cashAdvance->status,
                    'approved_by' => (int) ($cashAdvance->approved_by ?? 0),
                    'approved_at' => $cashAdvance->approved_at?->format('Y-m-d H:i:s'),
                    'installment_amount' => (float) ($cashAdvance->installment_amount ?? 0),
                    'installments_count' => (int) ($cashAdvance->installments_count ?? 0),
                ],
                [
                    'employee_id' => (int) $cashAdvance->employee_id,
                    'amount' => (float) ($cashAdvance->amount ?? 0),
                ],
                'Cash advance approved.'
            );

            return $cashAdvance;
        });
    }

    public function reject(CashAdvance $cashAdvance, array $validated, ?int $userId): CashAdvance
    {
        return DB::transaction(function () use ($cashAdvance, $validated, $userId) {
            $fromStatus = (string) $cashAdvance->status;

            if ($cashAdvance->status !== CashAdvance::STATUS_PENDING) {
                throw new RuntimeException('Only pending cash advances can be rejected.');
            }

            $cashAdvance->status = CashAdvance::STATUS_REJECTED;
            $cashAdvance->rejected_by = $userId;
            $cashAdvance->rejected_at = isset($validated['rejected_at']) && (string) $validated['rejected_at'] !== ''
                ? Carbon::parse((string) $validated['rejected_at'])
                : Carbon::now();

            $cashAdvance->approved_by = null;
            $cashAdvance->approved_at = null;

            $cashAdvance->decision_remarks = $validated['decision_remarks'] ?? null;
            $cashAdvance->save();

            $this->activityLogService->log('rejected', $cashAdvance, [
                'from_status' => $fromStatus,
                'to_status' => (string) $cashAdvance->status,
            ], 'Cash advance rejected.');

            app(\App\Services\AuditLogger::class)->log(
                'cash_advance.rejected',
                $cashAdvance,
                ['status' => $fromStatus],
                [
                    'status' => (string) $cashAdvance->status,
                    'rejected_by' => (int) ($cashAdvance->rejected_by ?? 0),
                    'rejected_at' => $cashAdvance->rejected_at?->format('Y-m-d H:i:s'),
                ],
                [
                    'employee_id' => (int) $cashAdvance->employee_id,
                    'amount' => (float) ($cashAdvance->amount ?? 0),
                ],
                'Cash advance rejected.'
            );

            return $cashAdvance;
        });
    }

    public function addDeduction(CashAdvance $cashAdvance, array $validated, ?int $userId): CashAdvanceDeduction
    {
        return DB::transaction(function () use ($cashAdvance, $validated, $userId) {
            $cashAdvance->loadMissing('deductions');

            if (!in_array((string) $cashAdvance->status, [CashAdvance::STATUS_APPROVED, CashAdvance::STATUS_COMPLETED], true)) {
                throw new RuntimeException('Only approved cash advances can receive deductions.');
            }

            $amount = (float) $validated['amount'];
            if ($amount <= 0) {
                throw new RuntimeException('Invalid deduction amount.');
            }

            $remaining = (float) $cashAdvance->remaining_balance;
            if ($amount > $remaining + 0.00001) {
                throw new RuntimeException('Deduction exceeds remaining balance.');
            }

            $deduction = new CashAdvanceDeduction();
            $deduction->cash_advance_id = (int) $cashAdvance->id;
            $deduction->deducted_at = Carbon::parse((string) $validated['deducted_at'])->toDateString();
            $deduction->amount = $amount;
            $deduction->notes = $validated['notes'] ?? null;
            $deduction->payroll_run_id = isset($validated['payroll_run_id']) ? (int) $validated['payroll_run_id'] : null;
            $deduction->created_by = $userId;
            $deduction->save();

            $cashAdvance->refresh();

            if ($cashAdvance->remaining_balance <= 0.00001) {
                $cashAdvance->status = CashAdvance::STATUS_COMPLETED;
                $cashAdvance->completed_by = $userId;
                $cashAdvance->completed_at = Carbon::now();
                $cashAdvance->save();
            }

            $this->activityLogService->log('deducted', $deduction, [
                'cash_advance_id' => (int) $cashAdvance->id,
                'employee_id' => (int) $cashAdvance->employee_id,
                'deducted_at' => (string) $deduction->deducted_at,
                'amount' => (float) ($deduction->amount ?? 0),
            ], 'Cash advance deduction recorded.');

            app(\App\Services\AuditLogger::class)->log(
                'cash_advance.deducted',
                $deduction,
                [],
                [
                    'cash_advance_id' => (int) $cashAdvance->id,
                    'employee_id' => (int) $cashAdvance->employee_id,
                    'deducted_at' => $deduction->deducted_at instanceof Carbon ? $deduction->deducted_at->format('Y-m-d') : (string) ($deduction->deducted_at ?? ''),
                    'amount' => (float) ($deduction->amount ?? 0),
                ],
                [],
                'Cash advance deduction recorded.'
            );

            return $deduction;
        });
    }

    private function hydrateMonthlySummary(Collection $totals): array
    {
        $employeeIds = $totals->map(fn ($r) => (int) ($r->employee_id ?? 0))->filter()->unique()->values();

        $employees = Employee::query()
            ->whereIn('employee_id', $employeeIds)
            ->get(['employee_id', 'employee_code', 'first_name', 'middle_name', 'last_name', 'suffix'])
            ->keyBy(fn (Employee $e) => (int) $e->employee_id);

        return $totals->map(function ($row) use ($employees) {
            $eid = (int) ($row->employee_id ?? 0);
            $employee = $employees->get($eid);

            return [
                'employee' => $employee ? $employee->only(['employee_id', 'employee_code', 'first_name', 'middle_name', 'last_name', 'suffix']) : null,
                'total_amount' => (float) ($row->total_amount ?? 0),
            ];
        })->values()->all();
    }

    private function storeAttachment(CashAdvance $cashAdvance, UploadedFile $file): void
    {
        $uuid = (string) Str::uuid();
        $path = sprintf('cash_advances/%d/%d/%s.bin', (int) $cashAdvance->company_id, (int) $cashAdvance->id, $uuid);

        $stored = $this->crypto->encryptAndStore($file, $path);

        try {
            $cashAdvance->attachment_original_name = $stored['original_name'];
            $cashAdvance->attachment_path = $stored['file_path'];
            $cashAdvance->attachment_mime_type = $stored['mime_type'];
            $cashAdvance->attachment_size = $stored['file_size'];
            $cashAdvance->attachment_is_encrypted = true;
            $cashAdvance->attachment_encryption_algo = $stored['algo'];
            $cashAdvance->attachment_encryption_iv = $stored['iv'];
            $cashAdvance->attachment_encryption_tag = $stored['tag'];
            $cashAdvance->attachment_key_version = $stored['key_version'];
            $cashAdvance->save();
        } catch (\Throwable $e) {
            $disk = (string) config('crewly.documents.disk', config('filesystems.default'));
            Storage::disk($disk)->delete($stored['file_path']);
            throw $e;
        }
    }
}
