<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\PayslipService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PayslipController extends Controller
{
    public function __construct(
        private readonly PayslipService $payslipService,
    ) {}

    public function show(Request $request, Employee $employee, string $period)
    {
        $user = $request->user();

        // HR/Admin can generate any employee payslip; employee portal users can only view their own.
        $canGenerateAll = $user?->can('access-payroll-summary') ?? false;
        if (!$canGenerateAll && (int) ($employee->user_id ?? 0) !== (int) ($user?->id ?? 0)) {
            abort(403);
        }

        [$from, $to] = $this->parsePeriodString($period);

        $payslip = $this->payslipService->build($employee, $from, $to, $user);

        $fromLabel = $from->format('Ymd');
        $toLabel = $to->format('Ymd');
        $filename = "payslip_{$employee->employee_id}_{$fromLabel}_{$toLabel}.pdf";

        $pdf = Pdf::loadView('payslips.payslip', [
            'payslip' => $payslip,
        ])->setPaper('A4');

        $download = (bool) $request->boolean('download');

        return $download ? $pdf->download($filename) : $pdf->stream($filename);
    }

    /**
     * Accepts formats like:
     * - 2026-03-01_2026-03-15
     * - 2026-03-01..2026-03-15
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function parsePeriodString(string $period): array
    {
        $period = trim($period);

        if (preg_match('/^(\d{4}-\d{2}-\d{2})\s*(?:_+|\.\.+)\s*(\d{4}-\d{2}-\d{2})$/', $period, $m) !== 1) {
            abort(422, 'Invalid payslip period format.');
        }

        $from = Carbon::parse($m[1])->startOfDay();
        $to = Carbon::parse($m[2])->startOfDay();

        if ($to->lessThan($from)) {
            [$from, $to] = [$to, $from];
        }

        // Safety: max 31 days inclusive.
        if ($from->diffInDays($to) > 31) {
            $to = $from->copy()->addDays(31);
        }

        return [$from, $to];
    }
}
