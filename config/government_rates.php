<?php

// Default government contribution rates.
//
// IMPORTANT:
// - This file is intentionally local/static data (no scraping).
// - Values are meant to pre-fill defaults and remain editable in Settings UI.

$sss2025 = (function () {
    // SSS Circular No. 2024-006 (Effective January 2025)
    // Schedule for Business Employers and Employees.
    //
    // This generates the salary ranges (0.., then 500-step brackets) and computes:
    // - Regular SS based on MSC capped at 20,000
    // - MPF contributions for MSC above 20,000 up to 35,000
    // - EC share: 10 for MSC <= 14,500, else 30
    //
    // Fields produced match `sss_contribution_tables` schema.

    $rows = [];

    $mscs = [];
    for ($msc = 4000; $msc <= 35000; $msc += 500) {
        $mscs[] = $msc;
    }

    foreach ($mscs as $msc) {
        $rangeFrom = $msc === 4000 ? 0.00 : ($msc - 250.00);
        $rangeTo = $msc === 35000 ? 9999999.99 : ($msc + 249.99);

        $regularMsc = min($msc, 20000.00);
        $mpfBase = max(0.0, $msc - 20000.00);

        $employeeRegular = $regularMsc * 0.05;
        $employerRegular = $regularMsc * 0.10;
        $employeeMpf = $mpfBase * 0.05;
        $employerMpf = $mpfBase * 0.10;

        $employeeShare = $employeeRegular + $employeeMpf;
        $employerShare = $employerRegular + $employerMpf;

        $ecShare = $regularMsc <= 14500.00 ? 10.00 : 30.00;

        $rows[] = [
            'range_from' => round($rangeFrom, 2),
            'range_to' => round($rangeTo, 2),
            'monthly_salary_credit' => round($msc, 2),
            'employee_share' => round($employeeShare, 2),
            'employer_share' => round($employerShare, 2),
            'ec_share' => round($ecShare, 2),
        ];
    }

    return $rows;
})();

return [
    'sss_2025' => $sss2025,

    'philhealth_2025' => [
        'effective_from' => '2025-01-01',
        'effective_to' => null,
        'premium_rate' => 0.05,
        'salary_floor' => 10000,
        'salary_ceiling' => 100000,
        'employee_share_percent' => 0.50,
        'employer_share_percent' => 0.50,
        'source_label' => 'PhilHealth 2024-2025 premium schedule',
        'source_reference_url' => null,
        'source_notes' => '5% premium, salary floor 10,000, salary ceiling 100,000',
    ],

    'pagibig_2025' => [
        'effective_from' => '2025-01-01',
        'effective_to' => null,
        'employee_rate_below_threshold' => 0.01,
        'employee_rate_above_threshold' => 0.02,
        'employer_rate' => 0.02,
        'salary_threshold' => 1500,
        'monthly_cap' => 200,
        'source_label' => 'Pag-IBIG default configured rate',
        'source_reference_url' => null,
        'source_notes' => 'Default 2025 configurable setting; review before production use',
    ],

    'meta' => [
        'sss_2025' => [
            'effective_from' => '2025-01-01',
            'effective_to' => null,
            'source_label' => 'SSS Circular No. 2024-006',
            'source_reference_url' => null,
            'source_notes' => 'Schedule of SSS Contributions Effective January 2025',
        ],
    ],
];
