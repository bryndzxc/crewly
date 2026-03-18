<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withholding_tax_tables', function (Blueprint $table) {
            $table->id();
            $table->enum('payroll_frequency', ['monthly', 'semi-monthly']);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->decimal('compensation_from', 12, 2)->default(0);
            $table->decimal('compensation_to', 12, 2)->nullable();
            $table->decimal('base_tax', 12, 2)->default(0);
            $table->decimal('percentage', 5, 4)->default(0);
            $table->decimal('excess_over', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['payroll_frequency', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withholding_tax_tables');
    }
};
