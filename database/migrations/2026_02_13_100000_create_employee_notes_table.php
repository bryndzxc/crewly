<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('employee_id');

            $table->string('note_type')->default('GENERAL');
            $table->text('note');
            $table->date('follow_up_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('visibility')->default('HR_ONLY');

            $table->timestamps();

            $table->index('employee_id');
            $table->index('note_type');
            $table->index('follow_up_date');

            $table->foreign('employee_id')
                ->references('employee_id')
                ->on('employees')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_notes');
    }
};
