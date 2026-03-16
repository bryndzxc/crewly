<?php

namespace App\Http\Controllers;

use App\Exports\PayrollSummaryExport;
use App\Services\PayslipService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class PayrollExportController extends Controller
{
    public function __construct(
        private readonly PayslipService $payslipService,
    ) {}

    public function export(Request $request)
    {
        $this->authorize('export-payroll-summary');

        $request->validate([
            'format' => ['required', 'in:csv,xlsx'],
        ]);

        [$from, $to] = $this->parsePeriod($request);

        $rows = $this->payslipService->buildPayrollExportRows($from, $to, $request->user());

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
                'Employee Name',
                'Base Salary',
                'Allowances',
                'Cash Advance',
                'SSS (Employee)',
                'PhilHealth (Employee)',
                'Pag-IBIG (Employee)',
                'Government Contributions (Employee Total)',
                'Estimated Gross',
                'Estimated Net',
            ]);

            foreach ($rows as $r) {
                fputcsv($out, [
                    (string) ($r['employee_name'] ?? ''),
                    number_format((float) ($r['base_salary'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['allowances'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['cash_advance'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['sss_employee'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['philhealth_employee'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['pagibig_employee'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['government_contributions_employee_total'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['estimated_gross'] ?? 0), 2, '.', ''),
                    number_format((float) ($r['estimated_net'] ?? 0), 2, '.', ''),
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
