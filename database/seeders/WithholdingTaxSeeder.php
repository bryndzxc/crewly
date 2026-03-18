<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * TRAIN Law withholding tax brackets (RA 10963, effective 2023 onwards).
 * Source: BIR Revenue Regulations No. 2-2023 / RR 11-2018 as amended.
 *
 * Formula per bracket: tax = base_tax + ((taxable_income - excess_over) * percentage)
 */
class WithholdingTaxSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('withholding_tax_tables')->delete();

        $effectiveFrom = '2023-01-01';

        // -------------------------------------------------------
        // Monthly brackets
        // -------------------------------------------------------
        $monthly = [
            [
                'compensation_from' => 0.00,
                'compensation_to'   => 20833.00,
                'base_tax'          => 0.00,
                'percentage'        => 0.0000,
                'excess_over'       => 0.00,
            ],
            [
                'compensation_from' => 20833.01,
                'compensation_to'   => 33332.00,
                'base_tax'          => 0.00,
                'percentage'        => 0.2000,
                'excess_over'       => 20833.00,
            ],
            [
                'compensation_from' => 33332.01,
                'compensation_to'   => 66666.00,
                'base_tax'          => 2500.00,
                'percentage'        => 0.2500,
                'excess_over'       => 33332.00,
            ],
            [
                'compensation_from' => 66666.01,
                'compensation_to'   => 166666.00,
                'base_tax'          => 10833.00,
                'percentage'        => 0.3000,
                'excess_over'       => 66666.00,
            ],
            [
                'compensation_from' => 166666.01,
                'compensation_to'   => 666666.00,
                'base_tax'          => 40833.33,
                'percentage'        => 0.3200,
                'excess_over'       => 166666.00,
            ],
            [
                'compensation_from' => 666666.01,
                'compensation_to'   => null,
                'base_tax'          => 200833.33,
                'percentage'        => 0.3500,
                'excess_over'       => 666666.00,
            ],
        ];

        foreach ($monthly as $bracket) {
            DB::table('withholding_tax_tables')->insert([
                'payroll_frequency' => 'monthly',
                'effective_from'    => $effectiveFrom,
                'effective_to'      => null,
                'compensation_from' => $bracket['compensation_from'],
                'compensation_to'   => $bracket['compensation_to'],
                'base_tax'          => $bracket['base_tax'],
                'percentage'        => $bracket['percentage'],
                'excess_over'       => $bracket['excess_over'],
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        // -------------------------------------------------------
        // Semi-monthly brackets (monthly values divided by 2)
        // -------------------------------------------------------
        $semiMonthly = [
            [
                'compensation_from' => 0.00,
                'compensation_to'   => 10417.00,
                'base_tax'          => 0.00,
                'percentage'        => 0.0000,
                'excess_over'       => 0.00,
            ],
            [
                'compensation_from' => 10417.01,
                'compensation_to'   => 16667.00,
                'base_tax'          => 0.00,
                'percentage'        => 0.2000,
                'excess_over'       => 10417.00,
            ],
            [
                'compensation_from' => 16667.01,
                'compensation_to'   => 33333.00,
                'base_tax'          => 1250.00,
                'percentage'        => 0.2500,
                'excess_over'       => 16667.00,
            ],
            [
                'compensation_from' => 33333.01,
                'compensation_to'   => 83333.00,
                'base_tax'          => 5416.67,
                'percentage'        => 0.3000,
                'excess_over'       => 33333.00,
            ],
            [
                'compensation_from' => 83333.01,
                'compensation_to'   => 333333.00,
                'base_tax'          => 20416.67,
                'percentage'        => 0.3200,
                'excess_over'       => 83333.00,
            ],
            [
                'compensation_from' => 333333.01,
                'compensation_to'   => null,
                'base_tax'          => 100416.67,
                'percentage'        => 0.3500,
                'excess_over'       => 333333.00,
            ],
        ];

        foreach ($semiMonthly as $bracket) {
            DB::table('withholding_tax_tables')->insert([
                'payroll_frequency' => 'semi-monthly',
                'effective_from'    => $effectiveFrom,
                'effective_to'      => null,
                'compensation_from' => $bracket['compensation_from'],
                'compensation_to'   => $bracket['compensation_to'],
                'base_tax'          => $bracket['base_tax'],
                'percentage'        => $bracket['percentage'],
                'excess_over'       => $bracket['excess_over'],
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }
}
