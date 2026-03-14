<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'sss_number')) {
                $table->string('sss_number')->nullable()->after('monthly_rate');
            }
            if (!Schema::hasColumn('employees', 'philhealth_number')) {
                $table->string('philhealth_number')->nullable()->after('sss_number');
            }
            if (!Schema::hasColumn('employees', 'pagibig_number')) {
                $table->string('pagibig_number')->nullable()->after('philhealth_number');
            }
            if (!Schema::hasColumn('employees', 'tin_number')) {
                $table->string('tin_number')->nullable()->after('pagibig_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $cols = [];
            foreach (['sss_number', 'philhealth_number', 'pagibig_number', 'tin_number'] as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $cols[] = $col;
                }
            }

            if (count($cols) > 0) {
                $table->dropColumn($cols);
            }
        });
    }
};
