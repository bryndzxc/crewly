<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\PagibigContributionSettingSeeder;
use Database\Seeders\PhilhealthContributionSettingSeeder;
use Database\Seeders\SssContributionTableSeeder;

class GovernmentContributionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PhilhealthContributionSettingSeeder::class,
            PagibigContributionSettingSeeder::class,
            SssContributionTableSeeder::class,
        ]);
    }
}
