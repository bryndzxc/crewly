<?php

namespace App\Repositories;

use App\Models\CashAdvance;
use App\Models\CashAdvanceDeduction;
use App\Models\Employee;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CashAdvanceRepository extends BaseRepository
{
    public function paginateIndex(
        string $status,
        int $employeeId,
        string $q,
        int $perPage
    ): LengthAwarePaginator {
        $query = CashAdvance::query()
            ->with([
                'employee:employee_id,employee_code,first_name,middle_name,last_name,suffix',
            ])
            ->withSum('deductions', 'amount');

        if ($status !== '' && in_array($status, [
            CashAdvance::STATUS_PENDING,
            CashAdvance::STATUS_APPROVED,
            CashAdvance::STATUS_REJECTED,
            CashAdvance::STATUS_COMPLETED,
        ], true)) {
            $query->where('status', $status);
        }

        if ($employeeId > 0) {
            $query->where('employee_id', $employeeId);
        }

        if ($q !== '') {
            $query->whereHas('employee', function ($empQ) use ($q) {
                $empQ->searchable($q);
            });
        }

        return $query
            ->orderByDesc('id')
            ->pagination($perPage);
    }

    /** @return Collection<int, Employee> */
    public function getEmployeesForFilter(int $limit = 250): Collection
    {
        return Employee::query()
            ->orderBy('employee_code')
            ->limit($limit)
            ->get(['employee_id', 'employee_code', 'first_name', 'middle_name', 'last_name', 'suffix', 'status']);
    }

    /**
     * Returns rows: employee_id, total_amount.
     *
     * @return Collection<int, object>
     */
    public function monthlyDeductionTotals(int $year, int $month, ?int $employeeId = null): Collection
    {
        return CashAdvanceDeduction::query()
            ->selectRaw('cash_advances.employee_id as employee_id, SUM(cash_advance_deductions.amount) as total_amount')
            ->join('cash_advances', 'cash_advances.id', '=', 'cash_advance_deductions.cash_advance_id')
            ->whereYear('cash_advance_deductions.deducted_at', $year)
            ->whereMonth('cash_advance_deductions.deducted_at', $month)
            ->when($employeeId && $employeeId > 0, function ($q) use ($employeeId) {
                $q->where('cash_advances.employee_id', $employeeId);
            })
            ->groupBy('cash_advances.employee_id')
            ->orderByDesc('total_amount')
            ->get();
    }

    public function loadShow(CashAdvance $cashAdvance): CashAdvance
    {
        $cashAdvance->load([
            'employee:employee_id,employee_code,first_name,middle_name,last_name,suffix',
            'requestedBy:id,name,role',
            'approvedBy:id,name,role',
            'rejectedBy:id,name,role',
            'completedBy:id,name,role',
            'deductions' => fn ($q) => $q->orderByDesc('deducted_at')->orderByDesc('id'),
            'deductions.createdBy:id,name,role',
        ]);

        return $cashAdvance;
    }
}
