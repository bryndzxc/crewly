<?php

namespace App\Services;

use App\Repositories\EmployeeRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EmployeesProbationService extends Service
{
    public function __construct(
        private readonly EmployeeRepository $employeeRepository,
    ) {}

    public function index(Request $request): array
    {
        $days = (int) $request->query('days', 30);
        if (!in_array($days, [30, 60, 90], true)) {
            $days = 30;
        }

        $search = trim((string) $request->query('search', ''));

        $today = Carbon::today();
        $end = $today->copy()->addDays($days);

        $allowedStatuses = (array) config('crewly.employees.probation_statuses', ['Active']);
        $allowedStatuses = array_values(array_filter(array_map('strval', $allowedStatuses)));

        $employees = $this->employeeRepository
            ->paginateProbationEndingSoon($today, $end, $allowedStatuses, $search, 15)
            ->through(function ($row) use ($today) {
                $date = $row->regularization_date;
                $daysRemaining = $date ? $today->diffInDays($date, false) : null;

                $fullName = trim(implode(' ', array_filter([
                    (string) ($row->first_name ?? ''),
                    (string) ($row->middle_name ?? ''),
                    (string) ($row->last_name ?? ''),
                    (string) ($row->suffix ?? ''),
                ])));

                return [
                    'employee_id' => $row->employee_id,
                    'employee_code' => $row->employee_code,
                    'full_name' => $fullName,
                    'department' => $row->department_name ?? null,
                    'regularization_date' => $row->regularization_date?->toDateString(),
                    'days_remaining' => $daysRemaining,
                    'status' => $row->status,
                ];
            });

        return [
            'employees' => $employees,
            'filters' => [
                'days' => $days,
                'search' => $search,
            ],
        ];
    }
}
