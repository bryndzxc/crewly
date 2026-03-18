<?php

namespace App\Services\Payroll;

use App\Models\WithholdingTaxTable;
use Illuminate\Support\Carbon;

class WithholdingTaxCalculator
{
    /**
     * Calculate Philippine withholding tax on compensation (TRAIN Law, 2023 onwards).
     *
     * @param  float   $taxableIncome  Gross pay minus mandatory employee contributions (SSS + PhilHealth + Pag-IBIG).
     * @param  string  $frequency      'monthly' or 'semi-monthly'. Returns zero tax for other frequencies.
     * @param  Carbon  $date           Reference date used to select the active tax table version.
     *
     * @return array{tax: float, bracket_id: int|null}
     */
    public function calculate(float $taxableIncome, string $frequency, Carbon $date): array
    {
        if (!in_array($frequency, ['monthly', 'semi-monthly'], true)) {
            return ['tax' => 0.0, 'bracket_id' => null];
        }

        if ($taxableIncome <= 0) {
            return ['tax' => 0.0, 'bracket_id' => null];
        }

        $bracket = WithholdingTaxTable::query()
            ->forFrequency($frequency)
            ->activeOn($date)
            ->where('compensation_from', '<=', $taxableIncome)
            ->where(function ($q) use ($taxableIncome) {
                $q->whereNull('compensation_to')
                    ->orWhere('compensation_to', '>=', $taxableIncome);
            })
            ->orderBy('compensation_from', 'desc')
            ->first();

        if (!$bracket) {
            return ['tax' => 0.0, 'bracket_id' => null];
        }

        $baseTax    = (float) $bracket->base_tax;
        $percentage = (float) $bracket->percentage;
        $excessOver = (float) $bracket->excess_over;

        $tax = $baseTax + (($taxableIncome - $excessOver) * $percentage);
        $tax = max(0.0, round($tax, 2));

        // Safety: tax must not exceed taxable income
        $tax = min($tax, $taxableIncome);

        return [
            'tax'        => $tax,
            'bracket_id' => (int) $bracket->id,
        ];
    }
}
