<?php

namespace App\Http\Controllers;

use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Services\PayrollRunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PayrollRunController extends Controller
{
    public function __construct(
        private readonly PayrollRunService $payrollRunService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('access-payroll-summary');

        [$from, $to] = $this->parsePeriod($request);
        $companyId = (int) ($request->user()?->company_id ?? 0);

        $payFrequency = $this->payrollRunService->inferPayFrequency($from, $to);
        $run = $companyId > 0 ? $this->payrollRunService->findRun($companyId, $from, $to, $payFrequency) : null;

        $payload = $this->payrollRunService->buildRegisterPayload($run);

        return Inertia::render('Payroll/Summary/Index', [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            ...$payload,
            'actions' => [
                'can_generate' => $request->user()?->can('generate-payroll') ?? false,
                'can_export' => $request->user()?->can('export-payroll-summary') ?? false,
                'can_edit' => $request->user()?->can('edit-payroll-deductions') ?? false,
                'can_review' => $request->user()?->can('review-payroll') ?? false,
                'can_finalize' => $request->user()?->can('finalize-payroll') ?? false,
                'can_release' => $request->user()?->can('release-payroll') ?? false,
            ],
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $this->authorize('generate-payroll');

        [$from, $to] = $this->parsePeriod($request);
        $companyId = (int) ($request->user()?->company_id ?? 0);
        $payFrequency = $this->payrollRunService->inferPayFrequency($from, $to);

        try {
            $this->payrollRunService->generateOrRegenerate($companyId, $from, $to, $payFrequency, (int) ($request->user()?->id ?? 0) ?: null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->setStatusCode(303);
        }

        return redirect()->route('payroll.summary.index', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ])->with('success', 'Payroll generated successfully.')->setStatusCode(303);
    }

    public function updateItem(Request $request, PayrollRunItem $item): RedirectResponse
    {
        $this->authorize('edit-payroll-deductions');

        $validated = $request->validate([
            'tax_deduction' => ['required', 'numeric', 'min:0'],
            'other_deductions' => ['required', 'numeric', 'min:0'],
            'deduction_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->payrollRunService->updateManualDeductions(
                $item,
                (float) $validated['tax_deduction'],
                (float) $validated['other_deductions'],
                $validated['deduction_notes'] ?? null,
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->setStatusCode(303);
        }

        return back()->with('success', 'Deductions updated.')->setStatusCode(303);
    }

    public function markReviewed(Request $request, PayrollRun $run): RedirectResponse
    {
        $this->authorize('review-payroll');

        try {
            $this->payrollRunService->transitionStatus($run, PayrollRun::STATUS_REVIEWED, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->setStatusCode(303);
        }

        return back()->with('success', 'Payroll marked as reviewed.')->setStatusCode(303);
    }

    public function finalize(Request $request, PayrollRun $run): RedirectResponse
    {
        $this->authorize('finalize-payroll');

        try {
            $this->payrollRunService->transitionStatus($run, PayrollRun::STATUS_FINALIZED, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->setStatusCode(303);
        }

        return back()->with('success', 'Payroll finalized.')->setStatusCode(303);
    }

    public function release(Request $request, PayrollRun $run): RedirectResponse
    {
        $this->authorize('release-payroll');

        try {
            $this->payrollRunService->transitionStatus($run, PayrollRun::STATUS_RELEASED, $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage())->setStatusCode(303);
        }

        return back()->with('success', 'Payroll released.')->setStatusCode(303);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function parsePeriod(Request $request): array
    {
        $defaultFrom = Carbon::today()->startOfMonth();
        $defaultTo = Carbon::today();

        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $from = $request->input('from') ? Carbon::parse((string) $request->input('from')) : ($request->query('from') ? Carbon::parse((string) $request->query('from')) : $defaultFrom);
        $to = $request->input('to') ? Carbon::parse((string) $request->input('to')) : ($request->query('to') ? Carbon::parse((string) $request->query('to')) : $defaultTo);

        if ($to->lessThan($from)) {
            [$from, $to] = [$to, $from];
        }

        if ($from->diffInDays($to) > 31) {
            $to = $from->copy()->addDays(31);
        }

        return [$from->startOfDay(), $to->startOfDay()];
    }
}
