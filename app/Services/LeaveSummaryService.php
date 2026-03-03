<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveSummaryService extends Service
{
    public function index(Request $request): array
    {
        $now = Carbon::now();

        $month = (int) $request->query('month', (int) $now->format('n'));
        if ($month < 1 || $month > 12) {
            $month = (int) $now->format('n');
        }

        $year = (int) $request->query('year', (int) $now->format('Y'));
        if ($year < 1970 || $year > 3000) {
            $year = (int) $now->format('Y');
        }

        $leaveTypeId = (int) $request->query('leave_type_id', 0);
        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 25);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $companyId = (int) ($request->user()?->company_id ?? 0);

        $query = $this->buildSummaryQuery(
            companyId: $companyId,
            year: $year,
            month: $month,
            leaveTypeId: $leaveTypeId,
            search: $q,
        );

        /** @var LengthAwarePaginator $summary */
        $summary = $query->pagination($perPage);
        $summary->setCollection($summary->getCollection()->map(function ($row) {
            $credits = (float) ($row->total_credits ?? 0);
            $usedMonthly = (float) ($row->used_monthly ?? 0);
            $usedYearly = (float) ($row->used_yearly ?? 0);

            return [
                'employee_id' => (int) $row->employee_id,
                'employee' => [
                    'employee_id' => (int) $row->employee_id,
                    'employee_code' => $row->employee_code,
                    'first_name' => $row->first_name,
                    'middle_name' => $row->middle_name,
                    'last_name' => $row->last_name,
                    'suffix' => $row->suffix,
                ],
                'employee_name' => $this->fullName($row),
                'leave_type_id' => (int) $row->leave_type_id,
                'leaveType' => [
                    'id' => (int) $row->leave_type_id,
                    'name' => $row->leave_type_name,
                    'code' => $row->leave_type_code,
                ],
                'total_credits' => round($credits, 2),
                'used_monthly' => round($usedMonthly, 2),
                'used_yearly' => round($usedYearly, 2),
                'remaining' => round(max(0.0, $credits - $usedYearly), 2),
            ];
        }));

        $leaveTypes = LeaveType::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return [
            'summary' => $summary,
            'leaveTypes' => $leaveTypes,
            'filters' => [
                'q' => $q,
                'month' => $month,
                'year' => $year,
                'leave_type_id' => $leaveTypeId ?: null,
                'per_page' => $perPage,
            ],
        ];
    }

    /**
     * Same query as index(), but returns a non-paginated collection-friendly Builder.
     */
    public function queryForExport(Request $request): Builder
    {
        $now = Carbon::now();

        $month = (int) $request->query('month', (int) $now->format('n'));
        if ($month < 1 || $month > 12) {
            $month = (int) $now->format('n');
        }

        $year = (int) $request->query('year', (int) $now->format('Y'));
        if ($year < 1970 || $year > 3000) {
            $year = (int) $now->format('Y');
        }

        $leaveTypeId = (int) $request->query('leave_type_id', 0);
        $q = trim((string) $request->query('q', ''));
        $companyId = (int) ($request->user()?->company_id ?? 0);

        return $this->buildSummaryQuery(
            companyId: $companyId,
            year: $year,
            month: $month,
            leaveTypeId: $leaveTypeId,
            search: $q,
        );
    }

    private function buildSummaryQuery(int $companyId, int $year, int $month, int $leaveTypeId, string $search): Builder
    {
        $monthStart = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $monthEnd = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        $yearStart = Carbon::create($year, 1, 1)->toDateString();
        $yearEnd = Carbon::create($year, 12, 31)->toDateString();

        $daysExpr = 'CASE WHEN leave_requests.is_half_day = 1 THEN 0.5 ELSE DATEDIFF(leave_requests.end_date, leave_requests.start_date) + 1 END';

        $monthlyAgg = DB::table('leave_requests')
            ->select([
                'employee_id',
                'leave_type_id',
                DB::raw('ROUND(SUM(' . $daysExpr . '), 2) as used_monthly'),
            ])
            ->where('company_id', $companyId)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereBetween('start_date', [$monthStart, $monthEnd])
            ->groupBy('employee_id', 'leave_type_id');

        $yearlyAgg = DB::table('leave_requests')
            ->select([
                'employee_id',
                'leave_type_id',
                DB::raw('ROUND(SUM(' . $daysExpr . '), 2) as used_yearly'),
            ])
            ->where('company_id', $companyId)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereBetween('start_date', [$yearStart, $yearEnd])
            ->groupBy('employee_id', 'leave_type_id');

        $balancesAgg = DB::table('leave_balances')
            ->select(['employee_id', 'leave_type_id', 'credits', 'used'])
            ->where('company_id', $companyId)
            ->where('year', $year);

        if ($leaveTypeId > 0) {
            $monthlyAgg->where('leave_type_id', $leaveTypeId);
            $yearlyAgg->where('leave_type_id', $leaveTypeId);
            $balancesAgg->where('leave_type_id', $leaveTypeId);
        }

        // Include employees with zero leave activity by using an Employees × Leave Types base.
        // By default, we only include active leave types (unless a specific type is requested).
        $query = Employee::query()
            ->join('leave_types', function ($join) use ($companyId, $leaveTypeId) {
                $join->on('leave_types.company_id', '=', 'employees.company_id')
                    ->where('leave_types.company_id', '=', $companyId);

                if ($leaveTypeId > 0) {
                    $join->where('leave_types.id', '=', $leaveTypeId);
                } else {
                    $join->where('leave_types.is_active', '=', 1);
                }
            })
            ->leftJoinSub($monthlyAgg, 'm', function ($join) {
                $join->on('m.employee_id', '=', 'employees.employee_id')
                    ->on('m.leave_type_id', '=', 'leave_types.id');
            })
            ->leftJoinSub($yearlyAgg, 'y', function ($join) {
                $join->on('y.employee_id', '=', 'employees.employee_id')
                    ->on('y.leave_type_id', '=', 'leave_types.id');
            })
            ->leftJoinSub($balancesAgg, 'b', function ($join) {
                $join->on('b.employee_id', '=', 'employees.employee_id')
                    ->on('b.leave_type_id', '=', 'leave_types.id');
            })
            ->addSelect([
                'employees.employee_id',
                'employees.employee_code',
                'employees.first_name',
                'employees.middle_name',
                'employees.last_name',
                'employees.suffix',
                DB::raw('leave_types.id as leave_type_id'),
                DB::raw('leave_types.name as leave_type_name'),
                DB::raw('leave_types.code as leave_type_code'),
                DB::raw('COALESCE(b.credits, leave_types.default_annual_credits, 0) as total_credits'),
                DB::raw('COALESCE(m.used_monthly, 0) as used_monthly'),
                DB::raw('COALESCE(b.used, y.used_yearly, 0) as used_yearly'),
            ])
            ->orderBy('employees.employee_code')
            ->orderBy('leave_types.name');

        if ($search !== '') {
            $query->searchable($search);
        }

        return $query;
    }

    private function fullName(object $row): string
    {
        $parts = [
            trim((string) ($row->first_name ?? '')),
            trim((string) ($row->middle_name ?? '')),
            trim((string) ($row->last_name ?? '')),
            trim((string) ($row->suffix ?? '')),
        ];

        $parts = array_values(array_filter($parts, fn ($v) => $v !== ''));

        return implode(' ', $parts);
    }
}
