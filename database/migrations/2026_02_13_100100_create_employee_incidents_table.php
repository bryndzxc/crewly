<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_incidents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('employee_id');

            $table->string('category');
            $table->date('incident_date');
            $table->text('description');
            $table->string('status')->default('OPEN');
            $table->text('action_taken')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();

            $table->timestamps();

            $table->index('employee_id');
            $table->index('status');
            $table->index('incident_date');
            $table->index('follow_up_date');

            $table->foreign('employee_id')
                ->references('employee_id')
                ->on('employees')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('assigned_to')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_incidents');
    }
};
