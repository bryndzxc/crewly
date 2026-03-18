<?php

namespace App\Http\Controllers;

use App\Exports\PayrollSummaryExport;
use App\Services\PayrollRunService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class PayrollExportController extends Controller
{
    public function __construct(
        private readonly PayrollRunService $payrollRunService,
    ) {}

    public function export(Request $request)
    {
        $this->authorize('export-payroll-summary');

        $request->validate([
            'format' => ['required', 'in:csv,xlsx'],
        ]);

        [$from, $to] = $this->parsePeriod($request);

        $companyId = (int) ($request->user()?->company_id ?? 0);
        $payFrequency = $this->payrollRunService->inferPayFrequency($from, $to);
        $run = $companyId > 0 ? $this->payrollRunService->findRun($companyId, $from, $to, $payFrequency) : null;
        $payload = $this->payrollRunService->buildRegisterPayload($run);
        $rows = (array) ($payload['rows'] ?? []);

        $fromLabel = $from->format('Ymd');
        $toLabel = $to->format('Ymd');
        $format = (string) $request->query('format');

        $baseFilename = "payroll_summary_{$fromLabel}_{$toLabel}";

        if ($format === 'xlsx') {
            return Excel::download(new PayrollSummaryExport($rows), $baseFilename . '.xlsx');
        }

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Employee Code',
                'Employee Name',
                'Department',
                'Position',
                'Basic Pay',
                'Allowances',
                'Other Earnings',
                'Gross Pay',
                'SSS (Employee)',
                'PhilHealth (Employee)',
                'Pag-IBIG (Employee)',
                'Cash Advance',
                'Tax',
                'Other Deductions',
                'Total Deductions',
                'Net Pay',
            ]);

            foreach ($rows as $r) {
                fputcsv($out, [
                    (string) ($r['employee_code'] ?? ''),
                    (string) ($r['employee_name'] ?? ''),
                    (string) ($r['department'] ?? ''),
                    (string) ($r['position_title'] ?? ''),
                    number_format((float) ($r['basic_pay'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['allowances_total'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['other_earnings'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['gross_pay'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['sss_employee'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['philhealth_employee'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['pagibig_employee'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['cash_advance_deduction'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['tax_deduction'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['other_deductions'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['total_deductions'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['net_pay'] ?? 0), 2, '.', ''),
                ]);
            }

            fclose($out);
        }, $baseFilename . '.csv', [
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
