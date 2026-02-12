<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ExpiringDocumentsController extends Controller
{
    public function index(Request $request): Response
    {
        $days = (int) $request->query('days', 30);
        if (!in_array($days, [30, 60, 90], true)) {
            $days = 30;
        }

        $expiredOnly = (bool) $request->boolean('expired', false);
        $search = trim((string) $request->query('search', ''));

        $today = Carbon::today();
        $end = $today->copy()->addDays($days);

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
                $expiredOnly,
                fn (Builder $q) => $q->whereDate('expiry_date', '<', $today->toDateString()),
                fn (Builder $q) => $q->whereBetween('expiry_date', [$today->toDateString(), $end->toDateString()])
            )
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $sub) use ($search) {
                    $sub->where('type', 'like', '%' . $search . '%')
                        ->orWhereHas('employee', fn (Builder $empQ) => $empQ->searchable($search));
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
                    'employee' => $employee ? [
                        'employee_id' => $employee->employee_id,
                        'employee_code' => $employee->employee_code,
                        'first_name' => $employee->first_name,
                        'middle_name' => $employee->middle_name,
                        'last_name' => $employee->last_name,
                        'suffix' => $employee->suffix,
                    ] : null,
                ];
            });

        return Inertia::render('Documents/ExpiringDocuments/Index', [
            'documents' => $documents,
            'filters' => [
                'days' => $days,
                'expired' => $expiredOnly ? 1 : 0,
                'search' => $search,
            ],
        ]);
    }
}
