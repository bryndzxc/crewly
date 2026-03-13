<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_allowances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('employee_id');
            $table->string('allowance_name');
            $table->decimal('amount', 12, 2);
            $table->enum('frequency', ['monthly', 'per_payroll']);
            $table->boolean('taxable')->default(false);
            $table->timestamps();

            $table->index('employee_id');
            $table->index('frequency');

            $table->foreign('employee_id')
                ->references('employee_id')
                ->on('employees')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_allowances');
    }
};