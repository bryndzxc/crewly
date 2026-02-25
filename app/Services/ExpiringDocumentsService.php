<?php

namespace App\Services;

use App\DTO\ExpiringDocumentsFilterData;
use App\Models\EmployeeDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExpiringDocumentsService extends Service
{
    /**
     * @return array{documents:mixed,filters:array{days:int,expired:int,search:string}}
     */
    public function index(Request $request): array
    {
        $filters = ExpiringDocumentsFilterData::fromRequest($request);

        $today = Carbon::today();
        $end = $today->copy()->addDays($filters->days);

        $documents = EmployeeDocument::query()
            ->select(['id', 'employee_id', 'type', 'expiry_date'])
            ->with([
                'employee' => function ($query) {
                    $query->select([
                        'employee_id',
                        'employee_code',
                        'first_name',
                        'middle_name',
                        'last_name',
                        'suffix',
                    ]);
                },
            ])
            ->whereNotNull('expiry_date')
            ->when(
                $filters->expiredOnly,
                fn (Builder $q) => $q->whereDate('expiry_date', '<', $today->toDateString()),
                fn (Builder $q) => $q->whereBetween('expiry_date', [$today->toDateString(), $end->toDateString()])
            )
            ->when($filters->search !== '', function (Builder $q) use ($filters) {
                $q->where(function (Builder $sub) use ($filters) {
                    $sub->where('type', 'like', '%' . $filters->search . '%')
                        ->orWhereHas('employee', fn (Builder $empQ) => $empQ->searchable($filters->search));
                });
            })
            ->orderBy('expiry_date', 'asc')
            ->paginate(15)
            ->withQueryString()
            ->through(function (EmployeeDocument $doc) {
                $doc->append(['expiry_status', 'days_to_expiry']);

                $employee = $doc->employee;

                return [
                    'id' => $doc->id,
                    'type' => $doc->type,
                    'expiry_date' => $doc->expiry_date?->toDateString(),
                    'expiry_status' => $doc->expiry_status,
                    'days_to_expiry' => $doc->days_to_expiry,
                    'employee' => $employee
                        ? [
                            'employee_id' => $employee->employee_id,
                            'employee_code' => $employee->employee_code,
                            'first_name' => $employee->first_name,
                            'middle_name' => $employee->middle_name,
                            'last_name' => $employee->last_name,
                            'suffix' => $employee->suffix,
                        ]
                        : null,
                ];
            });

        return [
            'documents' => $documents,
            'filters' => $filters->toFiltersArray(),
        ];
    }
}
