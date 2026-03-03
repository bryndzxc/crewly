<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Services\LeaveSummaryService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class LeaveSummaryController extends Controller
{
    public function __construct(
        private readonly LeaveSummaryService $leaveSummaryService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LeaveRequest::class);

        return Inertia::render('Leave/Summary/Index', $this->leaveSummaryService->index($request));
    }

    public function data(Request $request)
    {
        $this->authorize('viewAny', LeaveRequest::class);

        return response()->json($this->leaveSummaryService->index($request));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $rows = $this->leaveSummaryService->queryForExport($request)->get();

        $now = now();
        $year = (int) $request->query('year', (int) $now->format('Y'));
        $month = (int) $request->query('month', (int) $now->format('n'));
        $filename = sprintf('leave-summary-%04d-%02d.csv', $year, $month);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Employee ID',
                'Employee Name',
                'Leave Type',
                'Total Leave Credits',
                'Leaves Used (Monthly)',
                'Leaves Used (Yearly)',
                'Remaining Balance',
            ]);

            foreach ($rows as $row) {
                $credits = (float) ($row->total_credits ?? 0);
                $usedMonthly = (float) ($row->used_monthly ?? 0);
                $usedYearly = (float) ($row->used_yearly ?? 0);
                $remaining = max(0.0, $credits - $usedYearly);

                $nameParts = [
                    trim((string) ($row->first_name ?? '')),
                    trim((string) ($row->middle_name ?? '')),
                    trim((string) ($row->last_name ?? '')),
                    trim((string) ($row->suffix ?? '')),
                ];
                $nameParts = array_values(array_filter($nameParts, fn ($v) => $v !== ''));
                $employeeName = implode(' ', $nameParts);

                fputcsv($out, [
                    (int) $row->employee_id,
                    $employeeName,
                    (string) ($row->leave_type_name ?? ''),
                    number_format($credits, 2, '.', ''),
                    number_format($usedMonthly, 2, '.', ''),
                    number_format($usedYearly, 2, '.', ''),
                    number_format($remaining, 2, '.', ''),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
