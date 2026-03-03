<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('lead_type', 30)->nullable()->after('source_page');
            $table->string('employee_count_range', 30)->nullable()->after('lead_type');
            $table->string('industry', 120)->nullable()->after('employee_count_range');
            $table->text('current_process')->nullable()->after('industry');
            $table->text('biggest_pain')->nullable()->after('current_process');

            $table->index(['lead_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['lead_type', 'status']);

            $table->dropColumn([
                'lead_type',
                'employee_count_range',
                'industry',
                'current_process',
                'biggest_pain',
            ]);
        });
    }
};
