<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sss_contribution_tables')) {
            return;
        }

        Schema::create('sss_contribution_tables', function (Blueprint $table) {
            $table->id();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->decimal('range_from', 12, 2);
            $table->decimal('range_to', 12, 2);
            $table->decimal('monthly_salary_credit', 12, 2)->nullable();
            $table->decimal('employee_share', 12, 2);
            $table->decimal('employer_share', 12, 2);
            $table->decimal('ec_share', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['effective_from', 'effective_to']);
            $table->index(['range_from', 'range_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sss_contribution_tables');
    }
};
