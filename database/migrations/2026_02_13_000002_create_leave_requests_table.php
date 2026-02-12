<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('employee_id');
            $table->foreignId('leave_type_id')->constrained('leave_types');

            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_half_day')->default(false);
            $table->enum('half_day_part', ['AM', 'PM'])->nullable();

            $table->text('reason')->nullable();

            $table->enum('status', ['PENDING', 'APPROVED', 'DENIED', 'CANCELLED'])->default('PENDING');

            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();

            $table->foreignId('denied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('denied_at')->nullable();

            $table->text('decision_notes')->nullable();

            $table->timestamps();

            $table->foreign('employee_id')->references('employee_id')->on('employees')->cascadeOnDelete();

            $table->index(['employee_id']);
            $table->index(['leave_type_id']);
            $table->index(['status']);
            $table->index(['start_date']);
            $table->index(['end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
