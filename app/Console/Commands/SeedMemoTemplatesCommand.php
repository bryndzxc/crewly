<?php

namespace App\Console\Commands;

use Database\Seeders\MemoTemplateSeeder;
use Illuminate\Console\Command;
use Throwable;

class SeedMemoTemplatesCommand extends Command
{
    protected $signature = 'crewly:seed-memo-templates';

    protected $description = 'Create/update the built-in system memo templates (idempotent)';

    public function handle(): int
    {
        try {
            /** @var MemoTemplateSeeder $seeder */
            $seeder = app(MemoTemplateSeeder::class);
            $seeder->run();

            $this->info('Memo templates seeded/updated successfully.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Failed to seed memo templates.');
            $this->line($e->getMessage());
            $this->line('If this is a fresh setup, run: php artisan migrate --seed');

            return self::FAILURE;
        }
    }
}
