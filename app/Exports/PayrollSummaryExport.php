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
        ];
    }

    public function array(): array
    {
        return array_map(function (array $r) {
            return [
                (string) ($r['employee_name'] ?? ''),
                (float) ($r['base_salary'] ?? 0),
                (float) ($r['allowances'] ?? 0),
                (float) ($r['cash_advance'] ?? 0),
                (float) ($r['sss_employee'] ?? 0),
                (float) ($r['philhealth_employee'] ?? 0),
                (float) ($r['pagibig_employee'] ?? 0),
                (float) ($r['government_contributions_employee_total'] ?? 0),
                (float) ($r['estimated_gross'] ?? 0),
                (float) ($r['estimated_net'] ?? 0),
            ];
        }, $this->rows);
    }
}
