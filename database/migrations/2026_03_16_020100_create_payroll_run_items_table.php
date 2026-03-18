<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_run_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->unsignedInteger('employee_id');

            $table->decimal('basic_pay', 12, 2)->default(0);
            $table->decimal('allowances_total', 12, 2)->default(0);
            $table->decimal('other_earnings', 12, 2)->default(0);

            $table->decimal('gross_pay', 12, 2)->default(0);

            $table->decimal('sss_employee', 12, 2)->default(0);
            $table->decimal('philhealth_employee', 12, 2)->default(0);
            $table->decimal('pagibig_employee', 12, 2)->default(0);

            $table->decimal('cash_advance_deduction', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('tax_deduction', 12, 2)->default(0);

            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);

            $table->boolean('tax_overridden')->default(false);
            $table->text('deduction_notes')->nullable();

            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id'], 'payroll_run_items_run_employee_unique');
            $table->index(['payroll_run_id']);
            $table->index(['employee_id']);

            $table->foreign('employee_id')
                ->references('employee_id')
                ->on('employees')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_run_items');
    }
};
