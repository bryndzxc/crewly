<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class GovernmentRatesSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PhilHealthContribution2025Seeder::class,
            PagibigContribution2025Seeder::class,
            SssContribution2025Seeder::class,
        ]);
    }
}
