<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_advances', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedInteger('employee_id');

            $table->decimal('amount', 12, 2);
            $table->text('reason')->nullable();
            $table->date('requested_at');

            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'COMPLETED'])->default('PENDING');

            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();

            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('rejected_at')->nullable();

            $table->text('decision_remarks')->nullable();

            // Deduction plan (set upon approval; payroll integration can later consume this).
            $table->decimal('installment_amount', 12, 2)->nullable();
            $table->unsignedSmallInteger('installments_count')->nullable();

            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('completed_at')->nullable();

            // Optional attachment (encrypted-at-rest, stored on crewly.documents.disk).
            $table->string('attachment_original_name')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_mime_type')->nullable();
            $table->unsignedBigInteger('attachment_size')->nullable();

            $table->boolean('attachment_is_encrypted')->default(true);
            $table->string('attachment_encryption_algo')->default('AES-256-GCM');
            $table->text('attachment_encryption_iv')->nullable();
            $table->text('attachment_encryption_tag')->nullable();
            $table->unsignedSmallInteger('attachment_key_version')->default(1);

            $table->timestamps();

            $table->foreign('company_id', 'cash_advances_company_id_fk')
                ->references('id')
                ->on('companies')
                ->restrictOnDelete();

            $table->foreign('employee_id')
                ->references('employee_id')
                ->on('employees')
                ->cascadeOnDelete();

            $table->index(['company_id'], 'cash_advances_company_id_idx');
            $table->index(['employee_id']);
            $table->index(['status']);
            $table->index(['requested_at']);
            $table->index(['approved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_advances');
    }
};
