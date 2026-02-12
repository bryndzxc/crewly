<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // Laravel's `encrypted` cast produces base64-encoded payloads that can exceed VARCHAR/TEXT.
        // Use LONGTEXT for fields we want encrypted at rest.
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE employees MODIFY first_name LONGTEXT NOT NULL');
            DB::statement('ALTER TABLE employees MODIFY middle_name LONGTEXT NULL');
            DB::statement('ALTER TABLE employees MODIFY last_name LONGTEXT NOT NULL');
            DB::statement('ALTER TABLE employees MODIFY suffix LONGTEXT NULL');
            DB::statement('ALTER TABLE employees MODIFY position_title LONGTEXT NULL');
            DB::statement('ALTER TABLE employees MODIFY notes LONGTEXT NULL');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE employees MODIFY first_name VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE employees MODIFY middle_name VARCHAR(255) NULL');
            DB::statement('ALTER TABLE employees MODIFY last_name VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE employees MODIFY suffix VARCHAR(255) NULL');
            DB::statement('ALTER TABLE employees MODIFY position_title VARCHAR(255) NULL');
            DB::statement('ALTER TABLE employees MODIFY notes TEXT NULL');
        }
    }
};
