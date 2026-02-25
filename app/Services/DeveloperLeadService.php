<?php

namespace App\Services;

use App\Mail\DemoAccountApproved;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeIncident;
use App\Models\EmployeeNote;
use App\Models\Lead;
use App\Models\Memo;
use App\Models\MemoTemplate;
use App\Models\User;
use App\Repositories\CompanyRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DeveloperLeadService extends Service
{
    public function __construct(private readonly CompanyRepository $companyRepository)
    {
    }

    /**
     * @return array{filters:array{per_page:int},leads:mixed}
     */
    public function index(Request $request): array
    {
        $perPage = (int) $request->query('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $leads = Lead::query()
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $leads->setCollection(
            $leads->getCollection()->map(fn (Lead $lead) => [
                'id' => (int) $lead->id,
                'created_at' => $lead->created_at?->format('Y-m-d H:i:s'),
                'full_name' => (string) ($lead->full_name ?? ''),
                'company_name' => (string) ($lead->company_name ?? ''),
                'email' => (string) ($lead->email ?? ''),
                'phone' => (string) ($lead->phone ?? ''),
                'company_size' => (string) ($lead->company_size ?? ''),
                'message' => (string) ($lead->message ?? ''),
                'status' => (string) ($lead->status ?? Lead::STATUS_PENDING),
                'company_id' => $lead->company_id ? (int) $lead->company_id : null,
                'user_id' => $lead->user_id ? (int) $lead->user_id : null,
            ])
        );

        return [
            'filters' => [
                'per_page' => $perPage,
            ],
            'leads' => $leads,
        ];
    }

    public function approve(Lead $lead): Company
    {
        /** @var string $status */
        $status = (string) ($lead->status ?? Lead::STATUS_PENDING);
        if ($status === Lead::STATUS_APPROVED && $lead->company_id) {
            /** @var Company $company */
            $company = Company::query()->findOrFail($lead->company_id);

            return $company;
        }

        if ($status !== Lead::STATUS_PENDING) {
            throw new \RuntimeException('This demo request is already processed.');
        }

        $email = strtolower(trim((string) ($lead->email ?? '')));
        if ($email === '') {
            throw new \RuntimeException('Demo request is missing an email.');
        }

        if (User::withTrashed()->where('email', $email)->exists()) {
            throw new \RuntimeException('A user with this email already exists.');
        }

        $result = DB::transaction(function () use ($lead) {
            $companyName = trim((string) ($lead->company_name ?? ''));
            if ($companyName === '') {
                $companyName = 'New Company';
            }

            $companyAttributes = [
                'name' => $companyName,
                'slug' => $this->generateUniqueCompanySlug($companyName),
                'timezone' => (string) config('app.timezone', 'Asia/Manila'),
                'is_active' => true,
                'is_demo' => true,
            ];

            $company = $this->companyRepository->createCompany($companyAttributes);

            $passwordPlain = Str::random(16);

            $user = $this->companyRepository->createUserForCompany($company, [
                'name' => trim((string) ($lead->full_name ?? '')) ?: 'HR',
                'email' => strtolower(trim((string) ($lead->email ?? ''))),
                'role' => User::ROLE_HR,
                'password' => Hash::make($passwordPlain),
                'must_change_password' => true,
                'email_verified_at' => now(),
            ]);

            $this->seedDemoCompanyData($company, $user);

            $lead->forceFill([
                'status' => Lead::STATUS_APPROVED,
                'approved_at' => now(),
                'declined_at' => null,
                'company_id' => (int) $company->id,
                'user_id' => (int) $user->id,
            ])->save();

            return [$company, $user, $passwordPlain];
        });

        /** @var array{0:Company,1:User,2:string} $result */
        [$company, $user, $passwordPlain] = $result;

        $this->sendApprovalEmailBestEffort($company, $user, $passwordPlain);

        return $company;
    }

    public function decline(Lead $lead): void
    {
        /** @var string $status */
        $status = (string) ($lead->status ?? Lead::STATUS_PENDING);
        if ($status !== Lead::STATUS_PENDING) {
            throw new \RuntimeException('This demo request is already processed.');
        }

        $lead->forceFill([
            'status' => Lead::STATUS_DECLINED,
            'declined_at' => now(),
            'approved_at' => null,
            'company_id' => null,
            'user_id' => null,
        ])->save();
    }

    private function generateUniqueCompanySlug(string $companyName): string
    {
        $base = Str::slug($companyName);
        if ($base === '') {
            $base = 'company';
        }

        $slug = $base;
        $suffix = 2;
        while (Company::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function sendApprovalEmailBestEffort(Company $company, User $user, string $passwordPlain): void
    {
        try {
            $loginUrl = rtrim((string) config('app.url', url('/')), '/').'/login';

            Mail::to($user->email)->send(new DemoAccountApproved(
                company: $company,
                user: $user,
                passwordPlain: $passwordPlain,
                loginUrl: $loginUrl,
            ));
        } catch (\Throwable $e) {
            Log::warning('Failed sending demo approval email.', [
                'company_id' => (int) $company->id,
                'user_id' => (int) $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function seedDemoCompanyData(Company $company, User $createdByUser): void
    {
        $companyId = (int) $company->id;

        $existingEmployees = (int) Employee::withoutCompanyScope()->where('company_id', $companyId)->count();
        if ($existingEmployees > 0) {
            return;
        }

        $dept = Department::withoutCompanyScope()->withTrashed()->updateOrCreate(
            ['company_id' => $companyId, 'code' => 'OPS'],
            ['company_id' => $companyId, 'name' => 'Operations', 'code' => 'OPS', 'deleted_at' => null]
        );

        $employees = $this->seedEmployees($company, $dept->department_id, $createdByUser);
        $incidents = $this->seedIncidents($company, $employees, $createdByUser);
        $this->seedNotes($company, $employees, $createdByUser);
        $this->seedMemosWithAttachedPdfs($company, $employees, $incidents, $createdByUser);
    }

    /**
     * @return array<int, Employee>
     */
    private function seedEmployees(Company $company, int $departmentId, User $createdByUser): array
    {
        $companyId = (int) $company->id;

        $roles = [
            ['position' => 'Driver', 'count' => 6],
            ['position' => 'Helper', 'count' => 4],
            ['position' => 'Warehouse Staff', 'count' => 4],
            ['position' => 'Admin Assistant', 'count' => 2],
        ];

        $namePool = [
            ['first' => 'Juan', 'last' => 'Dela Cruz'],
            ['first' => 'Alex', 'last' => 'Santos'],
            ['first' => 'Mia', 'last' => 'Reyes'],
            ['first' => 'Noah', 'last' => 'Cruz'],
            ['first' => 'Ava', 'last' => 'Dela Rosa'],
            ['first' => 'Liam', 'last' => 'Garcia'],
            ['first' => 'Ella', 'last' => 'Torres'],
            ['first' => 'Ethan', 'last' => 'Flores'],
            ['first' => 'Sofia', 'last' => 'Navarro'],
            ['first' => 'Lucas', 'last' => 'Ramos'],
            ['first' => 'Chloe', 'last' => 'Castillo'],
            ['first' => 'Gabriel', 'last' => 'Mendoza'],
            ['first' => 'Isla', 'last' => 'Fernandez'],
            ['first' => 'Jacob', 'last' => 'Morales'],
            ['first' => 'Amara', 'last' => 'Villanueva'],
            ['first' => 'Daniel', 'last' => 'Ortega'],
            ['first' => 'Hannah', 'last' => 'Serrano'],
            ['first' => 'Marco', 'last' => 'Bautista'],
            ['first' => 'Bianca', 'last' => 'Aquino'],
            ['first' => 'Paolo', 'last' => 'Delgado'],
            ['first' => 'Rina', 'last' => 'Gonzales'],
        ];

        $employees = [];
        $inactiveIndexes = [3, 9, 14];
        $i = 0;

        foreach ($roles as $group) {
            for ($c = 0; $c < (int) $group['count']; $c++) {
                $name = $namePool[$i % count($namePool)];
                $employeeCode = strtoupper(Str::limit(Str::slug($company->slug ?: 'cmp'), 6, ''))
                    . '-' . $companyId . '-' . Str::upper(Str::random(6));

                $status = in_array($i, $inactiveIndexes, true) ? 'Inactive' : 'Active';

                $employees[] = Employee::withoutCompanyScope()->create([
                    'company_id' => $companyId,
                    'department_id' => (int) $departmentId,
                    'employee_code' => $employeeCode,
                    'first_name' => $name['first'],
                    'middle_name' => null,
                    'last_name' => $name['last'],
                    'suffix' => null,
                    'email' => strtolower($name['first'] . '.' . $name['last']) . "+{$companyId}@demo.crewly.test",
                    'mobile_number' => null,
                    'status' => $status,
                    'position_title' => (string) $group['position'],
                    'date_hired' => now()->subDays(30 + $i)->toDateString(),
                    'regularization_date' => now()->addDays(60)->toDateString(),
                    'employment_type' => 'Full-Time',
                    'notes' => null,
                    'created_by' => (int) $createdByUser->id,
                ]);

                $i++;
            }
        }

        // Ensure we stay within the requested range (12–20).
        return array_slice($employees, 0, 16);
    }

    /**
     * @param  array<int, Employee>  $employees
     * @return array<int, EmployeeIncident>
     */
    private function seedIncidents(Company $company, array $employees, User $createdByUser): array
    {
        $companyId = (int) $company->id;

        $pick = fn (int $idx) => $employees[$idx % max(1, count($employees))];

        $payloads = [
            [
                'employee' => $pick(0),
                'category' => 'Attendance',
                'date' => now()->subDays(12)->toDateString(),
                'description' => 'Late arrival without prior notice for two consecutive shifts.',
                'status' => EmployeeIncident::STATUS_OPEN,
            ],
            [
                'employee' => $pick(2),
                'category' => 'Safety',
                'date' => now()->subDays(9)->toDateString(),
                'description' => 'Incomplete PPE during warehouse loading activity.',
                'status' => EmployeeIncident::STATUS_OPEN,
            ],
            [
                'employee' => $pick(5),
                'category' => 'Performance',
                'date' => now()->subDays(20)->toDateString(),
                'description' => 'Repeated scanning errors causing inventory discrepancies.',
                'status' => EmployeeIncident::STATUS_UNDER_REVIEW,
            ],
            [
                'employee' => $pick(7),
                'category' => 'Conduct',
                'date' => now()->subDays(16)->toDateString(),
                'description' => 'Unprofessional language reported during team handoff.',
                'status' => EmployeeIncident::STATUS_UNDER_REVIEW,
            ],
            [
                'employee' => $pick(10),
                'category' => 'Quality',
                'date' => now()->subDays(28)->toDateString(),
                'description' => 'Delivery checklist not followed; documented corrective action completed.',
                'status' => EmployeeIncident::STATUS_CLOSED,
            ],
        ];

        $incidents = [];
        foreach ($payloads as $p) {
            /** @var Employee $employee */
            $employee = $p['employee'];
            $incidents[] = EmployeeIncident::withoutCompanyScope()->create([
                'company_id' => $companyId,
                'employee_id' => (int) $employee->employee_id,
                'category' => (string) $p['category'],
                'incident_date' => (string) $p['date'],
                'description' => (string) $p['description'],
                'status' => (string) $p['status'],
                'action_taken' => $p['status'] === EmployeeIncident::STATUS_CLOSED
                    ? 'Coaching provided; process checklist reviewed and signed.'
                    : null,
                'follow_up_date' => in_array($p['status'], [EmployeeIncident::STATUS_OPEN, EmployeeIncident::STATUS_UNDER_REVIEW], true)
                    ? now()->addDays(7)->toDateString()
                    : null,
                'created_by' => (int) $createdByUser->id,
                'assigned_to' => (int) $createdByUser->id,
            ]);
        }

        return $incidents;
    }

    /**
     * @param  array<int, Employee>  $employees
     */
    private function seedNotes(Company $company, array $employees, User $createdByUser): void
    {
        $companyId = (int) $company->id;

        $notes = [
            [EmployeeNote::TYPE_COACHING, 'Coached on timely pre-trip checks and documentation.'],
            [EmployeeNote::TYPE_WARNING, 'Reminder issued for repeated tardiness. Next occurrence may lead to formal action.'],
            [EmployeeNote::TYPE_COMMENDATION, 'Recognized for consistently accurate inventory counts.'],
            [EmployeeNote::TYPE_COACHING, 'Coached on safe lifting techniques and proper PPE use.'],
            [EmployeeNote::TYPE_GENERAL, 'Requested schedule adjustment for the next two weeks.'],
            [EmployeeNote::TYPE_COMMENDATION, 'Praised for assisting a new hire and improving onboarding speed.'],
            [EmployeeNote::TYPE_WARNING, 'Warned for incomplete end-of-shift handover notes.'],
            [EmployeeNote::TYPE_GENERAL, 'Follow-up planned to review performance improvement steps.'],
        ];

        for ($i = 0; $i < count($notes); $i++) {
            $employee = $employees[$i % max(1, count($employees))];
            [$type, $text] = $notes[$i];

            EmployeeNote::withoutCompanyScope()->create([
                'company_id' => $companyId,
                'employee_id' => (int) $employee->employee_id,
                'note_type' => (string) $type,
                'note' => (string) $text,
                'follow_up_date' => in_array($type, [EmployeeNote::TYPE_COACHING, EmployeeNote::TYPE_WARNING], true)
                    ? now()->addDays(10)->toDateString()
                    : null,
                'created_by' => (int) $createdByUser->id,
                'visibility' => EmployeeNote::VISIBILITY_HR_ONLY,
            ]);
        }
    }

    /**
     * @param  array<int, Employee>  $employees
     * @param  array<int, EmployeeIncident>  $incidents
     */
    private function seedMemosWithAttachedPdfs(Company $company, array $employees, array $incidents, User $createdByUser): void
    {
        $companyId = (int) $company->id;

                $nteBody = <<<'HTML'
<div>
    <p><strong>{{company_name}}</strong></p>
    <p>Date: {{memo_date}}</p>
    <p>To: {{employee_name}}</p>
    <p><strong>Subject: Notice to Explain (NTE)</strong></p>
    <p>
        This memo serves as a Notice to Explain regarding the following incident/concern:
    </p>
    <p>{{incident_description}}</p>
    <p>
        Please submit a written explanation within forty-eight (48) hours from receipt of this notice.
        Failure to respond within the stated period may require management to proceed based on available information.
    </p>
    <p>
        This notice is issued to ensure due process and to allow you the opportunity to provide your explanation.
    </p>
    <p>Sincerely,</p>
    <p><strong>Human Resources</strong></p>
</div>
HTML;

                $warningBody = <<<'HTML'
<div>
    <p><strong>{{company_name}}</strong></p>
    <p>Date: {{memo_date}}</p>
    <p>To: {{employee_name}}</p>
    <p><strong>Subject: Written Warning</strong></p>
    <p>
        Following our review of the matter below, this memo serves as a written warning:
    </p>
    <p>{{incident_description}}</p>
    <p>
        You are expected to comply with company policies and performance standards moving forward.
        Any repetition of similar behavior or continued failure to meet expectations may result in further disciplinary action,
        up to and including termination of employment.
    </p>
    <p>
        If you have questions regarding this warning or need clarification on expectations, please coordinate with your supervisor or HR.
    </p>
    <p>Sincerely,</p>
    <p><strong>Human Resources</strong></p>
</div>
HTML;

                $templateNte = MemoTemplate::withoutCompanyScope()->updateOrCreate(
                        ['company_id' => $companyId, 'slug' => 'notice-to-explain'],
                        [
                                'company_id' => $companyId,
                                'name' => 'Notice to Explain (NTE)',
                                'slug' => 'notice-to-explain',
                                'description' => 'Standard HR template for requesting an explanation (seeded for demo).',
                                'body_html' => $nteBody,
                                'is_active' => true,
                                'is_system' => false,
                                'created_by_user_id' => (int) $createdByUser->id,
                        ]
                );

                $templateWarning = MemoTemplate::withoutCompanyScope()->updateOrCreate(
                        ['company_id' => $companyId, 'slug' => 'written-warning'],
                        [
                                'company_id' => $companyId,
                                'name' => 'Written Warning',
                                'slug' => 'written-warning',
                                'description' => 'Standard HR template for issuing a written warning (seeded for demo).',
                                'body_html' => $warningBody,
                                'is_active' => true,
                                'is_system' => false,
                                'created_by_user_id' => (int) $createdByUser->id,
                        ]
                );

        $employee = $employees[0] ?? null;
        if (!$employee) {
            return;
        }

        $incident = $incidents[0] ?? null;

        $ntePdf = $this->copySeedPdfForCompany($company, 'nte');
        $warningPdf = $this->copySeedPdfForCompany($company, 'written-warning');

        Memo::withoutCompanyScope()->create([
            'company_id' => $companyId,
            'employee_id' => (int) $employee->employee_id,
            'incident_id' => $incident ? (int) $incident->id : null,
            'memo_template_id' => (int) $templateNte->id,
            'title' => 'Generated NTE - '.$employee->employee_code,
            'body_rendered_html' => '<div>Generated NTE (seeded)</div>',
            'pdf_path' => $ntePdf,
            'status' => 'generated',
            'created_by_user_id' => (int) $createdByUser->id,
        ]);

        Memo::withoutCompanyScope()->create([
            'company_id' => $companyId,
            'employee_id' => (int) $employee->employee_id,
            'incident_id' => $incident ? (int) $incident->id : null,
            'memo_template_id' => (int) $templateWarning->id,
            'title' => 'Written Warning - '.$employee->employee_code,
            'body_rendered_html' => '<div>Written warning (seeded)</div>',
            'pdf_path' => $warningPdf,
            'status' => 'generated',
            'created_by_user_id' => (int) $createdByUser->id,
        ]);
    }

    private function copySeedPdfForCompany(Company $company, string $type): string
    {
        $slug = (string) ($company->slug ?: 'company');

        $sourceCandidates = [
            'memos/seed/demo-logistics-inc/gmJYQY5fU6cv.pdf',
            'memos/seed/sample-construction-co/fyZJNkbSthD0.pdf',
        ];

        $source = null;
        foreach ($sourceCandidates as $candidate) {
            if (Storage::disk('local')->exists($candidate)) {
                $source = $candidate;
                break;
            }
        }

        $target = 'memos/seed/'.$slug.'/'.$type.'-'.Str::random(12).'.pdf';

        if ($source) {
            Storage::disk('local')->copy($source, $target);

            return $target;
        }

        // Fallback: write a minimal PDF header so download works.
        Storage::disk('local')->put($target, "%PDF-1.4\n% Seeded memo ({$type})\n");

        return $target;
    }
}
