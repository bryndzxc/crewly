<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use App\Mail\InvoiceSummaryEmail;
use App\Models\Company;
use App\Models\CrewlyNotification;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompanyBillingController extends Controller
{
    private function defaultPaymentInstructions(): string
    {
        $accountName = trim((string) config('crewly.billing.account_name', ''));
        $gcash = trim((string) config('crewly.billing.gcash_number', ''));
        $maya = trim((string) config('crewly.billing.maya_number', ''));
        $bankNote = trim((string) config('crewly.billing.bank_note', ''));

        $lines = ['Payment options:'];

        if ($gcash !== '') {
            $lines[] = '- GCash: '.$gcash.($accountName !== '' ? ' (Name: '.$accountName.')' : '');
        }
        if ($maya !== '') {
            $lines[] = '- Maya: '.$maya.($accountName !== '' ? ' (Name: '.$accountName.')' : '');
        }
        if ($bankNote !== '') {
            $lines[] = '- '.$bankNote;
        }

        return trim(implode("\n", $lines));
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Company::class);

        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $perPage = min(max((int) $request->query('per_page', 15), 5), 100);

        $query = Company::query()->selectedFields();

        // Demo companies are not billable and tend to create noise in billing screens.
        $query->where('is_demo', false);

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            });
        }

        if ($status !== '') {
            $query->where('subscription_status', $status);
        }

        $companies = $query
            ->orderByDesc('updated_at')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Company $c) => [
                'id' => (int) $c->id,
                'name' => (string) $c->name,
                'slug' => (string) ($c->slug ?? ''),
                'is_active' => (bool) $c->is_active,
                'plan_name' => (string) ($c->plan_name ?? ''),
                'max_employees' => (int) ($c->max_employees ?? 0),
                'subscription_status' => (string) ($c->subscription_status ?? ''),
                'trial_ends_at' => $c->trial_ends_at?->format('Y-m-d H:i:s'),
                'next_billing_at' => $c->next_billing_at?->format('Y-m-d H:i:s'),
                'last_payment_at' => $c->last_payment_at?->format('Y-m-d H:i:s'),
                'grace_days' => (int) ($c->grace_days ?? 7),
            ]);

        return Inertia::render('Admin/Billing/Companies/Index', [
            'filters' => [
                'q' => $q,
                'status' => $status,
                'per_page' => $perPage,
            ],
            'companies' => $companies,
            'statuses' => [Company::SUB_TRIAL, Company::SUB_ACTIVE, Company::SUB_PAST_DUE, Company::SUB_SUSPENDED],
            'plans' => [Company::PLAN_STARTER, Company::PLAN_GROWTH, Company::PLAN_PRO],
        ]);
    }

    public function show(Request $request, Company $company): Response
    {
        $this->authorize('view', $company);

        $users = $company->users()
            ->orderByRaw("case when role = 'admin' then 0 when role = 'hr' then 1 else 9 end")
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email', 'role']);

        return Inertia::render('Admin/Billing/Companies/Show', [
            'company' => [
                'id' => (int) $company->id,
                'name' => (string) $company->name,
                'slug' => (string) ($company->slug ?? ''),
                'is_active' => (bool) $company->is_active,
                'is_demo' => (bool) $company->is_demo,
                'plan_name' => (string) ($company->plan_name ?? ''),
                'max_employees' => (int) ($company->max_employees ?? 0),
                'subscription_status' => (string) ($company->subscription_status ?? ''),
                'trial_ends_at' => $company->trial_ends_at?->format('Y-m-d H:i:s'),
                'next_billing_at' => $company->next_billing_at?->format('Y-m-d H:i:s'),
                'last_payment_at' => $company->last_payment_at?->format('Y-m-d H:i:s'),
                'grace_days' => (int) ($company->grace_days ?? 7),
                'billing_notes' => trim((string) ($company->billing_notes ?? '')) !== ''
                    ? (string) $company->billing_notes
                    : $this->defaultPaymentInstructions(),
            ],
            'users' => $users->map(fn (User $u) => [
                'id' => (int) $u->id,
                'name' => (string) $u->name,
                'email' => (string) $u->email,
                'role' => (string) $u->role,
            ])->values()->all(),
            'statuses' => [Company::SUB_TRIAL, Company::SUB_ACTIVE, Company::SUB_PAST_DUE, Company::SUB_SUSPENDED],
            'plans' => [
                ['id' => Company::PLAN_STARTER, 'label' => 'Starter'],
                ['id' => Company::PLAN_GROWTH, 'label' => 'Growth'],
                ['id' => Company::PLAN_PRO, 'label' => 'Pro'],
            ],
        ]);
    }

    public function activate(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $now = now();
        $before = $company->getAttributes();

        $company->subscription_status = Company::SUB_ACTIVE;
        $company->last_payment_at = $now;
        $company->next_billing_at = $now->copy()->addDays(30);
        $company->save();

        app(AuditLogger::class)->log(
            'billing.activate',
            $company,
            $before,
            $company->getAttributes(),
            ['company_id' => (int) $company->id],
            "Activated plan '{$company->plan_name}' and set next billing."
        );

        $this->notifyCompanyAdmins($company, 'BILLING_ACTIVE', 'Subscription activated', 'Your subscription has been activated.');

        return redirect()
            ->back()
            ->with('success', 'Subscription activated (manual).')
            ->setStatusCode(303);
    }

    public function markPaid(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $validated = $request->validate([
            'paid_at' => ['nullable', 'date'],
        ]);

        $paidAt = isset($validated['paid_at']) && $validated['paid_at']
            ? now()->parse((string) $validated['paid_at'])
            : now();

        $before = $company->getAttributes();

        $company->subscription_status = Company::SUB_ACTIVE;
        $company->last_payment_at = $paidAt;
        $company->next_billing_at = $paidAt->copy()->addDays(30);
        $company->save();

        app(AuditLogger::class)->log(
            'billing.mark_paid',
            $company,
            $before,
            $company->getAttributes(),
            ['company_id' => (int) $company->id],
            'Marked invoice as paid.'
        );

        $this->notifyCompanyAdmins($company, 'BILLING_PAID', 'Payment recorded', 'Payment was recorded and billing date updated.');

        return redirect()->back()->with('success', 'Marked as paid.')->setStatusCode(303);
    }

    public function setPastDue(Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $before = $company->getAttributes();
        $company->subscription_status = Company::SUB_PAST_DUE;
        $company->save();

        app(AuditLogger::class)->log(
            'billing.set_past_due',
            $company,
            $before,
            $company->getAttributes(),
            ['company_id' => (int) $company->id],
            'Set subscription to past due.'
        );

        $this->notifyCompanyAdmins($company, 'BILLING_PAST_DUE', 'Billing past due', 'Your billing is past due. Please contact support to settle your invoice.');

        return redirect()->back()->with('success', 'Set to past due.')->setStatusCode(303);
    }

    public function suspend(Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $before = $company->getAttributes();
        $company->subscription_status = Company::SUB_SUSPENDED;
        $company->save();

        app(AuditLogger::class)->log(
            'billing.suspend',
            $company,
            $before,
            $company->getAttributes(),
            ['company_id' => (int) $company->id],
            'Suspended subscription.'
        );

        $this->notifyCompanyAdmins($company, 'BILLING_SUSPENDED', 'Subscription suspended', 'Your subscription was suspended. Please contact support to restore access.');

        return redirect()->back()->with('success', 'Suspended.')->setStatusCode(303);
    }

    public function grantTrial30(Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $before = $company->getAttributes();
        $trialEndsAt = now()->addDays(30);

        $company->subscription_status = Company::SUB_TRIAL;
        $company->trial_ends_at = $trialEndsAt;
        $company->next_billing_at = $trialEndsAt;
        $company->is_active = true;
        $company->save();

        app(AuditLogger::class)->log(
            'billing.grant_trial_30',
            $company,
            $before,
            $company->getAttributes(),
            ['company_id' => (int) $company->id, 'trial_ends_at' => $trialEndsAt->format('Y-m-d H:i:s')],
            'Granted 30-day free trial.'
        );

        $this->notifyCompanyAdmins($company, 'BILLING_TRIAL', 'Free trial granted', 'A 30-day free trial has been applied to your account.');

        return redirect()->back()->with('success', 'Granted 30-day trial.')->setStatusCode(303);
    }

    public function sendInvoiceEmail(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $recipients = $company->users()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_HR])
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get(['id', 'email']);

        if ($recipients->count() === 0) {
            return redirect()
                ->back()
                ->with('error', 'No admin/HR users found to email.')
                ->setStatusCode(303);
        }

        $emails = $recipients
            ->pluck('email')
            ->map(fn ($e) => strtolower(trim((string) $e)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        try {
            Mail::to($emails)->send(new InvoiceSummaryEmail($company));
        } catch (\Throwable $e) {
            Log::warning('Failed sending invoice summary email.', [
                'company_id' => (int) $company->id,
                'error' => $e->getMessage(),
            ]);

            $message = 'Failed to send invoice email. Check logs/mail configuration.';
            if (config('app.debug')) {
                $message = 'Failed to send invoice email: '.$e->getMessage();
            }

            return redirect()
                ->back()
                ->with('error', $message)
                ->setStatusCode(303);
        }

        app(AuditLogger::class)->log(
            'billing.send_invoice_email',
            $company,
            $company->getAttributes(),
            $company->getAttributes(),
            ['company_id' => (int) $company->id, 'recipients' => $emails],
            'Sent invoice summary email.'
        );

        return redirect()
            ->back()
            ->with('success', 'Invoice email sent.')
            ->setStatusCode(303);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $validated = $request->validate([
            'plan_name' => ['required', 'string', Rule::in([Company::PLAN_STARTER, Company::PLAN_GROWTH, Company::PLAN_PRO])],
            'max_employees' => ['required', 'integer', 'min:1', 'max:100000'],
            'subscription_status' => ['required', 'string', Rule::in([Company::SUB_TRIAL, Company::SUB_ACTIVE, Company::SUB_PAST_DUE, Company::SUB_SUSPENDED])],
            'trial_ends_at' => ['nullable', 'date'],
            'next_billing_at' => ['nullable', 'date'],
            'last_payment_at' => ['nullable', 'date'],
            'grace_days' => ['required', 'integer', 'min:0', 'max:365'],
            'billing_notes' => ['nullable', 'string'],
        ]);

        $before = $company->getAttributes();

        $company->plan_name = (string) $validated['plan_name'];
        $company->max_employees = (int) $validated['max_employees'];
        $company->subscription_status = (string) $validated['subscription_status'];
        $company->trial_ends_at = $validated['trial_ends_at'] ? now()->parse((string) $validated['trial_ends_at']) : null;
        $company->next_billing_at = $validated['next_billing_at'] ? now()->parse((string) $validated['next_billing_at']) : null;
        $company->last_payment_at = $validated['last_payment_at'] ? now()->parse((string) $validated['last_payment_at']) : null;
        $company->grace_days = (int) $validated['grace_days'];
        $company->billing_notes = $validated['billing_notes'] ? (string) $validated['billing_notes'] : null;
        $company->save();

        app(AuditLogger::class)->log(
            'billing.update',
            $company,
            $before,
            $company->getAttributes(),
            ['company_id' => (int) $company->id],
            'Updated billing fields.'
        );

        return redirect()->back()->with('success', 'Billing settings updated.')->setStatusCode(303);
    }

    private function notifyCompanyAdmins(Company $company, string $type, string $title, string $body): void
    {
        try {
            $ids = app(NotificationService::class)->hrAdminRecipientIdsForCompany((int) $company->id);
            if (count($ids) === 0) {
                return;
            }

            foreach ($ids as $userId) {
                app(NotificationService::class)->createForUser(
                    userId: (int) $userId,
                    companyId: (int) $company->id,
                    type: $type,
                    title: $title,
                    body: $body,
                    url: null,
                    severity: CrewlyNotification::SEVERITY_WARNING,
                    data: [
                        'company_id' => (int) $company->id,
                        'subscription_status' => (string) ($company->subscription_status ?? ''),
                        'next_billing_at' => $company->next_billing_at?->format('Y-m-d H:i:s'),
                    ],
                    dedupeKey: null
                );
            }
        } catch (\Throwable $e) {
            // Best-effort.
        }
    }
}
