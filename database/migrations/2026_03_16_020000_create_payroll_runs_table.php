<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('pay_frequency', ['weekly', 'semi-monthly', 'monthly']);
            $table->enum('status', ['draft', 'reviewed', 'finalized', 'released'])->default('draft');

            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('finalized_at')->nullable();

            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('released_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'period_start', 'period_end', 'pay_frequency'], 'payroll_runs_company_period_frequency_unique');
            $table->index(['company_id', 'period_start', 'period_end']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
