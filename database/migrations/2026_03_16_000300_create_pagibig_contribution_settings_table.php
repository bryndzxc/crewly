<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pagibig_contribution_settings')) {
            Schema::create('pagibig_contribution_settings', function (Blueprint $table) {
                $table->id();
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->decimal('employee_rate_below_threshold', 5, 4)->nullable();
                $table->decimal('employee_rate_above_threshold', 5, 4)->nullable();
                $table->decimal('employer_rate', 5, 4)->nullable();
                $table->decimal('salary_threshold', 12, 2)->nullable();
                $table->decimal('monthly_cap', 12, 2)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['effective_from', 'effective_to'], 'pi_effective_idx');
            });

            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        $indexExists = false;

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $indexExists = count(DB::select("SHOW INDEX FROM `pagibig_contribution_settings` WHERE Key_name = 'pi_effective_idx'")) > 0;
        } elseif ($driver === 'sqlite') {
            $rows = DB::select("PRAGMA index_list('pagibig_contribution_settings')");
            foreach ($rows as $row) {
                if (($row->name ?? null) === 'pi_effective_idx') {
                    $indexExists = true;
                    break;
                }
            }
        } elseif ($driver === 'pgsql') {
            $indexExists = count(DB::select(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                ['pagibig_contribution_settings', 'pi_effective_idx']
            )) > 0;
        }

        if (!$indexExists) {
            Schema::table('pagibig_contribution_settings', function (Blueprint $table) {
                $table->index(['effective_from', 'effective_to'], 'pi_effective_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pagibig_contribution_settings');
    }
};
