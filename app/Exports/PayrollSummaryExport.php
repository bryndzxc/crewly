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
            'Estimated Gross',
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
                (float) ($r['estimated_gross'] ?? 0),
            ];
        }, $this->rows);
    }
}
