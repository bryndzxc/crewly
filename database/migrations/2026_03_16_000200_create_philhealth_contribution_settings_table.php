<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('philhealth_contribution_settings')) {
            Schema::create('philhealth_contribution_settings', function (Blueprint $table) {
                $table->id();
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->decimal('premium_rate', 5, 4);
                $table->decimal('salary_floor', 12, 2);
                $table->decimal('salary_ceiling', 12, 2);
                $table->decimal('employee_share_percent', 5, 4);
                $table->decimal('employer_share_percent', 5, 4);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['effective_from', 'effective_to'], 'ph_effective_idx');
            });

            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        $indexExists = false;

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $indexExists = count(DB::select("SHOW INDEX FROM `philhealth_contribution_settings` WHERE Key_name = 'ph_effective_idx'")) > 0;
        } elseif ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA index_list('philhealth_contribution_settings')");
            foreach ($rows as $row) {
                if (($row->name ?? null) === 'ph_effective_idx') {
                    $indexExists = true;
                    break;
                }
            }
        } elseif ($driver === 'pgsql') {
            $indexExists = count(DB::select(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                ['philhealth_contribution_settings', 'ph_effective_idx']
            )) > 0;
        }

        if (!$indexExists) {
            Schema::table('philhealth_contribution_settings', function (Blueprint $table) {
                $table->index(['effective_from', 'effective_to'], 'ph_effective_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('philhealth_contribution_settings');
    }
};
