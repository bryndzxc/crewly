<?php

namespace Database\Seeders;

use App\Services\GovernmentDefaultsService;
use Illuminate\Database\Seeder;

class PhilHealthContribution2025Seeder extends Seeder
{
    public function run(): void
    {
        app(GovernmentDefaultsService::class)->loadPhilhealth2025();
    }
}
