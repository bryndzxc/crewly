<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAllowance;
use App\Models\EmployeeCompensation;
use App\Models\EmployeeDocument;
use App\Models\EmployeeIncident;
use App\Models\EmployeeNote;
use App\Models\EmployeeSalaryHistory;
use App\Models\Memo;
use App\Models\MemoTemplate;
use App\Resources\EmployeeResource;
use App\Services\EmployeeService;
use App\Services\PlanLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    private EmployeeService $employeeService;

    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Employees/Index', $this->employeeService->index($request));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Employees/Create', [
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['department_id', 'name', 'code']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmployeeRequest $request, PlanLimitService $planLimitService): RedirectResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $companyId = (int) ($user?->company_id ?? 0);

        if ($companyId > 0 && $user && !$user->isDeveloper()) {
            $usage = $planLimitService->employeeUsage($companyId);

            if (($usage['max'] ?? 0) > 0 && (int) ($usage['used'] ?? 0) >= (int) ($usage['max'] ?? 0)) {
                $max = (int) ($usage['max'] ?? 0);
                $msg = "You've reached your plan limit of {$max} active employees. Set an employee to Inactive/Terminated/Resigned, or contact support to upgrade.";

                session()->flash('error', $msg);
                session()->flash('upgrade_url', route('chat.support', [
                    'message' => "Hi! We reached our plan limit ({$max} active employees). Please help us upgrade.",
                ]));
                session()->flash('upgrade_label', 'Contact support to upgrade');

                return back()->setStatusCode(303);
            }
        }

        $portalCreatedNew = false;
        $portalPasswordPlain = null;

        try {
            $employee = DB::transaction(function () use ($validated, &$portalCreatedNew, &$portalPasswordPlain) {
                $result = $this->employeeService->createEmployeeAndPortalUser($validated);
                $portalCreatedNew = (bool) ($result['portal_created_new'] ?? false);
                $portalPasswordPlain = is_string($result['portal_password_plain'] ?? null) ? (string) $result['portal_password_plain'] : null;
                return $result['employee'];
            });

            $this->employeeService->finalizeEmployeeCreation($employee, $validated, $portalCreatedNew, $portalPasswordPlain);
        } catch (\Throwable $e) {
            Log::warning('Employee create failed.', [
                'company_id' => $companyId,
                'user_id' => (int) ($user?->id ?? 0),
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', 'Could not create employee. Please verify the email is unique to your company, or contact support.');
            session()->flash('upgrade_url', route('chat.support', [
                'message' => 'Hi! We are having trouble creating an employee portal user. Can you help?',
            ]));
            session()->flash('upgrade_label', 'Contact support');

            return back()->withInput()->setStatusCode(303);
        }

        return to_route('employees.index')->setStatusCode(303);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Employee $employee)
    {
        if ($request->expectsJson()) {
            return new EmployeeResource($employee);
        }

        $documents = EmployeeDocument::query()
            ->where('employee_id', (int) $employee->employee_id)
            ->orderByDesc('id')
            ->get([
                'id',
                'employee_id',
                'type',
                'original_name',
                'mime_type',
                'file_size',
                'issue_date',
                'expiry_date',
                'notes',
                'uploaded_by',
                'created_at',
            ]);

        $compensation = EmployeeCompensation::query()
            ->where('employee_id', (int) $employee->employee_id)
            ->first([
                'id',
                'employee_id',
                'salary_type',
                'base_salary',
                'pay_frequency',
                'effective_date',
                'notes',
                'created_at',
                'updated_at',
            ]);

        $allowances = EmployeeAllowance::query()
            ->where('employee_id', (int) $employee->employee_id)
            ->orderBy('allowance_name')
            ->orderBy('id')
            ->get([
                'id',
                'employee_id',
                'allowance_name',
                'amount',
                'frequency',
                'taxable',
                'created_at',
                'updated_at',
            ]);

        $salaryHistory = EmployeeSalaryHistory::query()
            ->where('employee_id', (int) $employee->employee_id)
            ->with('approvedBy:id,name')
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->get([
                'id',
                'employee_id',
                'previous_salary',
                'new_salary',
                'effective_date',
                'reason',
                'approved_by',
                'created_at',
                'updated_at',
            ]);

        $user = $request->user();
        $canViewRelations = $user ? Gate::forUser($user)->check('employees-relations-view') : false;
        $canGenerateMemos = $user ? Gate::forUser($user)->check('generate-memos') : false;

        $notesPayload = [];
        $incidentsPayload = [];
        $memoTemplatesPayload = [];
        $memosPayload = [];

        if ($canViewRelations) {
            $notes = EmployeeNote::query()
                ->where('employee_id', (int) $employee->employee_id)
                ->with([
                    'creator:id,name',
                    'attachments',
                    'attachments.uploader:id,name',
                ])
                ->orderByDesc('id')
                ->get();

            $notesPayload = $notes->map(function (EmployeeNote $note) {
                return [
                    'id' => $note->id,
                    'note_type' => $note->note_type,
                    'note' => $note->note,
                    'follow_up_date' => $note->follow_up_date?->toDateString(),
                    'created_at' => $note->created_at?->format('Y-m-d H:i:s'),
                    'created_by' => $note->creator ? $note->creator->only(['id', 'name']) : null,
                    'attachments' => $note->attachments
                        ->sortByDesc('id')
                        ->map(fn ($a) => [
                            'id' => $a->id,
                            'type' => $a->type,
                            'original_name' => $a->original_name,
                            'mime_type' => $a->mime_type,
                            'file_size' => $a->file_size,
                            'uploaded_by' => $a->uploader ? $a->uploader->only(['id', 'name']) : null,
                            'created_at' => $a->created_at?->format('Y-m-d H:i:s'),
                        ])->values()->all(),
                ];
            })->values()->all();

            $incidents = EmployeeIncident::query()
                ->where('employee_id', (int) $employee->employee_id)
                ->with([
                    'creator:id,name',
                    'assignee:id,name',
                    'attachments',
                    'attachments.uploader:id,name',
                ])
                ->orderByRaw("FIELD(status, 'OPEN', 'UNDER_REVIEW', 'RESOLVED', 'CLOSED')")
                ->orderByDesc('incident_date')
                ->orderByDesc('id')
                ->get();

            $incidentsPayload = $incidents->map(function (EmployeeIncident $incident) {
                return [
                    'id' => $incident->id,
                    'category' => $incident->category,
                    'incident_date' => $incident->incident_date?->toDateString(),
                    'description' => $incident->description,
                    'status' => $incident->status,
                    'action_taken' => $incident->action_taken,
                    'follow_up_date' => $incident->follow_up_date?->toDateString(),
                    'created_at' => $incident->created_at?->format('Y-m-d H:i:s'),
                    'created_by' => $incident->creator ? $incident->creator->only(['id', 'name']) : null,
                    'assigned_to' => $incident->assignee ? $incident->assignee->only(['id', 'name']) : null,
                    'attachments' => $incident->attachments
                        ->sortByDesc('id')
                        ->map(fn ($a) => [
                            'id' => $a->id,
                            'type' => $a->type,
                            'original_name' => $a->original_name,
                            'mime_type' => $a->mime_type,
                            'file_size' => $a->file_size,
                            'uploaded_by' => $a->uploader ? $a->uploader->only(['id', 'name']) : null,
                            'created_at' => $a->created_at?->format('Y-m-d H:i:s'),
                        ])->values()->all(),
                ];
            })->values()->all();

            if ($canGenerateMemos) {
                $memoTemplatesPayload = MemoTemplate::query()
                    ->where('is_active', true)
                    ->orderByDesc('is_system')
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->values()
                    ->all();
            }

            $memos = Memo::query()
                ->where('employee_id', (int) $employee->employee_id)
                ->with([
                    'template:id,name',
                ])
                ->orderByDesc('id')
                ->limit(50)
                ->get();

            $memosPayload = $memos->map(function (Memo $memo) {
                return [
                    'id' => $memo->id,
                    'title' => $memo->title,
                    'status' => $memo->status,
                    'memo_template' => $memo->template ? $memo->template->only(['id', 'name']) : null,
                    'incident_id' => $memo->incident_id,
                    'created_at' => $memo->created_at?->format('Y-m-d H:i:s'),
                ];
            })->values()->all();
        }

        return Inertia::render('Employees/Show', [
            'employee' => (new EmployeeResource($employee))->toArray($request),
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['department_id', 'name', 'code']),
            'compensation' => $compensation ? [
                'id' => $compensation->id,
                'employee_id' => $compensation->employee_id,
                'salary_type' => $compensation->salary_type,
                'base_salary' => $compensation->base_salary,
                'pay_frequency' => $compensation->pay_frequency,
                'effective_date' => $compensation->effective_date?->format('Y-m-d'),
                'notes' => $compensation->notes,
                'created_at' => $compensation->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $compensation->updated_at?->format('Y-m-d H:i:s'),
            ] : null,
            'allowances' => $allowances->map(fn (EmployeeAllowance $allowance) => [
                'id' => $allowance->id,
                'employee_id' => $allowance->employee_id,
                'allowance_name' => $allowance->allowance_name,
                'amount' => $allowance->amount,
                'frequency' => $allowance->frequency,
                'taxable' => (bool) $allowance->taxable,
                'created_at' => $allowance->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $allowance->updated_at?->format('Y-m-d H:i:s'),
            ])->values()->all(),
            'salaryHistory' => $salaryHistory->map(fn (EmployeeSalaryHistory $history) => [
                'id' => $history->id,
                'employee_id' => $history->employee_id,
                'previous_salary' => $history->previous_salary,
                'new_salary' => $history->new_salary,
                'effective_date' => $history->effective_date?->format('Y-m-d'),
                'reason' => $history->reason,
                'approved_by' => $history->approvedBy ? $history->approvedBy->only(['id', 'name']) : null,
                'created_at' => $history->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $history->updated_at?->format('Y-m-d H:i:s'),
            ])->values()->all(),
            'documents' => $documents,
            'notes' => $notesPayload,
            'incidents' => $incidentsPayload,
            'memoTemplates' => $memoTemplatesPayload,
            'memos' => $memosPayload,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee): Response
    {
        $payload = $employee->only([
            'employee_id',
            'department_id',
            'employee_code',
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
            'email',
            'mobile_number',
            'status',
            'position_title',
            'employment_type',
            'monthly_rate',
            'notes',
        ]);

        $payload['date_hired'] = $employee->date_hired?->format('Y-m-d');
        $payload['regularization_date'] = $employee->regularization_date?->format('Y-m-d');

        return Inertia::render('Employees/Edit', [
            'employee' => $payload,
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['department_id', 'name', 'code']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validated();
        $this->employeeService->update($employee, $validated);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $this->employeeService->delete($employee);

        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}
