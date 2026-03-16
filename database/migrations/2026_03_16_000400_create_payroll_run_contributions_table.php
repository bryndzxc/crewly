<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payroll_run_contributions')) {
            Schema::drop('payroll_run_contributions');
        }

        Schema::create('payroll_run_contributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id');
            $table->date('payroll_period_start');
            $table->date('payroll_period_end');
            $table->decimal('base_salary', 12, 2);

            // Effective (used in payroll/net pay): may be computed or overridden.
            $table->decimal('sss_employee', 12, 2)->default(0);
            $table->decimal('sss_employer', 12, 2)->default(0);
            $table->decimal('philhealth_employee', 12, 2)->default(0);
            $table->decimal('philhealth_employer', 12, 2)->default(0);
            $table->decimal('pagibig_employee', 12, 2)->default(0);
            $table->decimal('pagibig_employer', 12, 2)->default(0);

            // Last computed values (kept for audit/reference even after overrides).
            $table->decimal('sss_employee_computed', 12, 2)->default(0);
            $table->decimal('sss_employer_computed', 12, 2)->default(0);
            $table->decimal('philhealth_employee_computed', 12, 2)->default(0);
            $table->decimal('philhealth_employer_computed', 12, 2)->default(0);
            $table->decimal('pagibig_employee_computed', 12, 2)->default(0);
            $table->decimal('pagibig_employer_computed', 12, 2)->default(0);

            $table->boolean('sss_overridden')->default(false);
            $table->boolean('philhealth_overridden')->default(false);
            $table->boolean('pagibig_overridden')->default(false);
            $table->text('override_notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'payroll_period_start', 'payroll_period_end'], 'payroll_run_contrib_employee_period_unique');
            $table->index(['payroll_period_start', 'payroll_period_end'], 'prc_period_idx');

            $table->foreign('employee_id')->references('employee_id')->on('employees')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_run_contributions');
    }
};
