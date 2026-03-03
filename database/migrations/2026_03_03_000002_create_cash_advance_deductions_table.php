<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_advance_deductions', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('cash_advance_id');

            $table->date('deducted_at');
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();

            // Future payroll integration hook.
            $table->unsignedBigInteger('payroll_run_id')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->foreign('company_id', 'cash_advance_deductions_company_id_fk')
                ->references('id')
                ->on('companies')
                ->restrictOnDelete();

            $table->foreign('cash_advance_id')
                ->references('id')
                ->on('cash_advances')
                ->cascadeOnDelete();

            $table->index(['company_id'], 'cash_advance_deductions_company_id_idx');
            $table->index(['cash_advance_id']);
            $table->index(['deducted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_advance_deductions');
    }
};
