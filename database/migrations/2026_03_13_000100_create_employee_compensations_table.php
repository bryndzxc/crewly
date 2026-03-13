<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_compensations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('employee_id');
            $table->enum('salary_type', ['monthly', 'daily', 'hourly']);
            $table->decimal('base_salary', 12, 2);
            $table->enum('pay_frequency', ['monthly', 'semi-monthly', 'weekly']);
            $table->date('effective_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('employee_id');
            $table->index('effective_date');

            $table->foreign('employee_id')
                ->references('employee_id')
                ->on('employees')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_compensations');
    }
};