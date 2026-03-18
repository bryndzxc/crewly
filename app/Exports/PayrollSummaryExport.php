<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PayrollSummaryExport implements FromArray, WithHeadings
{
    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public function __construct(
        private readonly array $rows,
    ) {}

    public function headings(): array
    {
        return [
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
        ];
    }

    public function array(): array
    {
        return array_map(function (array $r) {
            return [
                (string) ($r['employee_code'] ?? ''),
                (string) ($r['employee_name'] ?? ''),
                (string) ($r['department'] ?? ''),
                (string) ($r['position_title'] ?? ''),
                (float) ($r['basic_pay'] ?? 0),
                (float) ($r['allowances_total'] ?? 0),
                (float) ($r['other_earnings'] ?? 0),
                (float) ($r['gross_pay'] ?? 0),
                (float) ($r['sss_employee'] ?? 0),
                (float) ($r['philhealth_employee'] ?? 0),
                (float) ($r['pagibig_employee'] ?? 0),
                (float) ($r['cash_advance_deduction'] ?? 0),
                (float) ($r['tax_deduction'] ?? 0),
                (float) ($r['other_deductions'] ?? 0),
                (float) ($r['total_deductions'] ?? 0),
                (float) ($r['net_pay'] ?? 0),
            ];
        }, $this->rows);
    }
}
