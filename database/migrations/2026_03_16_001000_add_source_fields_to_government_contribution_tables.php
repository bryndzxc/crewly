<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addSourceFieldsIfMissing('sss_contribution_tables');
        $this->addSourceFieldsIfMissing('philhealth_contribution_settings');
        $this->addSourceFieldsIfMissing('pagibig_contribution_settings');
    }

    public function down(): void
    {
        $this->dropSourceFieldsIfPresent('sss_contribution_tables');
        $this->dropSourceFieldsIfPresent('philhealth_contribution_settings');
        $this->dropSourceFieldsIfPresent('pagibig_contribution_settings');
    }

    private function addSourceFieldsIfMissing(string $table): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (!Schema::hasColumn($table, 'source_label')) {
                $blueprint->string('source_label')->nullable()->after('notes');
            }
            if (!Schema::hasColumn($table, 'source_reference_url')) {
                $blueprint->string('source_reference_url')->nullable()->after('source_label');
            }
            if (!Schema::hasColumn($table, 'source_notes')) {
                $blueprint->text('source_notes')->nullable()->after('source_reference_url');
            }
        });
    }

    private function dropSourceFieldsIfPresent(string $table): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            $cols = [];
            foreach (['source_label', 'source_reference_url', 'source_notes'] as $col) {
                if (Schema::hasColumn($table, $col)) {
                    $cols[] = $col;
                }
            }

            if (count($cols) > 0) {
                $blueprint->dropColumn($cols);
            }
        });
    }
};
