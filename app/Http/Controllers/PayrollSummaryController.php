<?php

namespace App\Http\Controllers;

use App\Services\PayrollSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class PayrollSummaryController extends Controller
{
    public function __construct(
        private readonly PayrollSummaryService $payrollSummaryService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('access-payroll-summary');

        [$from, $to] = $this->parsePeriod($request);
        $payload = $this->payrollSummaryService->generate($from, $to, $request->user());

        return Inertia::render('Payroll/Summary/Index', [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            ...$payload,
            'actions' => [
                'can_export' => $request->user()?->can('export-payroll-summary') ?? false,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('export-payroll-summary');

        $request->validate([
            'format' => ['required', 'in:csv'],
        ]);

        [$from, $to] = $this->parsePeriod($request);
        $payload = $this->payrollSummaryService->generate($from, $to, $request->user());

        $fromLabel = $from->format('Ymd');
        $toLabel = $to->format('Ymd');
        $filename = "payroll_summary_{$fromLabel}_{$toLabel}.csv";

        $rows = (array) ($payload['rows'] ?? []);

        app(\App\Services\AuditLogger::class)->log(
            'payroll.summary.exported',
            null,
            [],
            [],
            [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'format' => (string) $request->query('format', 'csv'),
                'row_count' => count($rows),
                'filename' => $filename,
            ],
            'Payroll summary exported.'
        );

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Employee Code',
                'Employee Name',
                'Department',
                'Present',
                'Absent',
                'On Leave',
                'Worked Hours',
                'Late (mins)',
                'Undertime (mins)',
                'Overtime (mins)',
            ]);

            foreach ($rows as $r) {
                fputcsv($out, [
                    (string) ($r['employee_code'] ?? ''),
                    (string) ($r['employee_name'] ?? ''),
                    (string) ($r['department'] ?? ''),
                    (int) ($r['present_days'] ?? 0),
                    (int) ($r['absent_days'] ?? 0),
                    (int) ($r['on_leave_days'] ?? 0),
                    number_format((float) ($r['worked_hours'] ?? 0), 2, '.', ''),
                    (int) ($r['late_minutes'] ?? 0),
                    (int) ($r['undertime_minutes'] ?? 0),
                    (int) ($r['overtime_minutes'] ?? 0),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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

        $from = $request->query('from') ? Carbon::parse((string) $request->query('from')) : $defaultFrom;
        $to = $request->query('to') ? Carbon::parse((string) $request->query('to')) : $defaultTo;

        if ($to->lessThan($from)) {
            [$from, $to] = [$to, $from];
        }

        // Safety: max 31 days (inclusive).
        if ($from->diffInDays($to) > 31) {
            $to = $from->copy()->addDays(31);
        }

        return [$from->startOfDay(), $to->startOfDay()];
    }
}
