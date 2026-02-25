<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'company_id')) {
            Schema::table('departments', function (Blueprint $table) {
                // Original migration created a global unique(code). Make it per-company.
                $table->dropUnique('departments_code_unique');
                $table->unique(['company_id', 'code'], 'departments_company_code_unique');
            });
        }

        if (Schema::hasTable('leave_types') && Schema::hasColumn('leave_types', 'company_id')) {
            Schema::table('leave_types', function (Blueprint $table) {
                $table->dropUnique('leave_types_code_unique');
                $table->unique(['company_id', 'code'], 'leave_types_company_code_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'company_id')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropUnique('departments_company_code_unique');
                $table->unique('code', 'departments_code_unique');
            });
        }

        if (Schema::hasTable('leave_types') && Schema::hasColumn('leave_types', 'company_id')) {
            Schema::table('leave_types', function (Blueprint $table) {
                $table->dropUnique('leave_types_company_code_unique');
                $table->unique('code', 'leave_types_code_unique');
            });
        }
    }
};
