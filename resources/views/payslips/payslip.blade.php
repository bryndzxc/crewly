<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Payslip</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        .muted { color: #6b7280; }
        .h1 { font-size: 16px; font-weight: 700; margin: 0; }
        .h2 { font-size: 12px; font-weight: 700; margin: 0; }
        .row { width: 100%; }
        .box { border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; }
        .mt { margin-top: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; }
        th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #374151; }
        td.num { text-align: right; }
        .total-row td { font-weight: 700; }
    </style>
</head>
<body>
@php
    $companyName = (string) data_get($payslip, 'company.name', '');
    $employeeName = (string) data_get($payslip, 'employee.name', '');
    $positionTitle = (string) data_get($payslip, 'employee.position_title', '');
    $from = (string) data_get($payslip, 'period.from', '');
    $to = (string) data_get($payslip, 'period.to', '');

    $baseSalary = (float) data_get($payslip, 'earnings.base_salary', 0);
    $allowancesTotal = (float) data_get($payslip, 'earnings.allowances_total', 0);
    $otherEarnings = (float) data_get($payslip, 'earnings.other_earnings', 0);

    $sss = (float) data_get($payslip, 'deductions.sss', 0);
    $philhealth = (float) data_get($payslip, 'deductions.philhealth', 0);
    $pagibig = (float) data_get($payslip, 'deductions.pagibig', 0);
    $governmentTotal = (float) data_get($payslip, 'deductions.government_total', 0);

    $cashAdvances = (float) data_get($payslip, 'deductions.cash_advances', 0);
    $tax = (float) data_get($payslip, 'deductions.tax', 0);
    $otherDeductions = (float) data_get($payslip, 'deductions.other_deductions', 0);

    $grossPay = (float) data_get($payslip, 'totals.gross_pay', 0);
    $totalDeductions = (float) data_get($payslip, 'totals.total_deductions', 0);
    $netPay = (float) data_get($payslip, 'totals.net_pay', 0);
@endphp

<div class="box">
    <div class="row">
        <p class="h1">Payslip</p>
        <p class="muted" style="margin: 2px 0 0;">Pay Period: {{ $from }} to {{ $to }}</p>
    </div>

    <div class="mt">
        <table>
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <p class="h2">Company</p>
                    <p style="margin: 2px 0 0;">{{ $companyName !== '' ? $companyName : '—' }}</p>
                </td>
                <td style="width: 50%; vertical-align: top;">
                    <p class="h2">Employee</p>
                    <p style="margin: 2px 0 0;">{{ $employeeName !== '' ? $employeeName : '—' }}</p>
                    <p class="muted" style="margin: 2px 0 0;">{{ $positionTitle !== '' ? $positionTitle : '—' }}</p>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="box mt">
    <p class="h2">Earnings</p>
    <table style="margin-top: 8px;">
        <thead>
            <tr>
                <th>Description</th>
                <th class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Base Salary</td>
                <td class="num">{{ number_format($baseSalary, 2, '.', ',') }}</td>
            </tr>
            <tr>
                <td>Allowances (sum)</td>
                <td class="num">{{ number_format($allowancesTotal, 2, '.', ',') }}</td>
            </tr>
            <tr>
                <td>Other earnings</td>
                <td class="num">{{ number_format($otherEarnings, 2, '.', ',') }}</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="box mt">
    <p class="h2">Deductions</p>
    <table style="margin-top: 8px;">
        <thead>
            <tr>
                <th>Description</th>
                <th class="num">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>SSS (Employee)</td>
                <td class="num">{{ number_format($sss, 2, '.', ',') }}</td>
            </tr>
            <tr>
                <td>PhilHealth (Employee)</td>
                <td class="num">{{ number_format($philhealth, 2, '.', ',') }}</td>
            </tr>
            <tr>
                <td>Pag-IBIG (Employee)</td>
                <td class="num">{{ number_format($pagibig, 2, '.', ',') }}</td>
            </tr>
            <tr class="total-row">
                <td>Government Contributions (Employee total)</td>
                <td class="num">{{ number_format($governmentTotal, 2, '.', ',') }}</td>
            </tr>
            <tr>
                <td>Cash Advances</td>
                <td class="num">{{ number_format($cashAdvances, 2, '.', ',') }}</td>
            </tr>
            <tr>
                <td>Tax</td>
                <td class="num">{{ number_format($tax, 2, '.', ',') }}</td>
            </tr>
            <tr>
                <td>Other deductions</td>
                <td class="num">{{ number_format($otherDeductions, 2, '.', ',') }}</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="box mt">
    <p class="h2">Totals</p>
    <table style="margin-top: 8px;">
        <tbody>
            <tr class="total-row">
                <td>Gross Pay</td>
                <td class="num">{{ number_format($grossPay, 2, '.', ',') }}</td>
            </tr>
            <tr class="total-row">
                <td>Total Deductions</td>
                <td class="num">{{ number_format($totalDeductions, 2, '.', ',') }}</td>
            </tr>
            <tr class="total-row">
                <td>Net Pay</td>
                <td class="num">{{ number_format($netPay, 2, '.', ',') }}</td>
            </tr>
        </tbody>
    </table>
    <p class="muted" style="margin: 8px 0 0;">Gross Pay = Earnings total. Net Pay = Gross Pay - Total Deductions.</p>
</div>

</body>
</html>
