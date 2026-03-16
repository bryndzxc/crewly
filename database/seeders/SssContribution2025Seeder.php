<?php

namespace Database\Seeders;

use App\Services\GovernmentDefaultsService;
use Illuminate\Database\Seeder;

class SssContribution2025Seeder extends Seeder
{
    public function run(): void
    {
        app(GovernmentDefaultsService::class)->loadSss2025();
    }
}
