<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->increments('employee_id');
            $table->unsignedInteger('department_id');
            $table->string('employee_code')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->longText('email');
            $table->longText('mobile_number')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'On Leave', 'Terminated', 'Resigned'])->default('Active');
            $table->string('position_title')->nullable();
            $table->date('date_hired')->nullable();
            $table->date('regularization_date')->nullable();
            $table->enum('employment_type', ['Full-Time', 'Part-Time', 'Contractor', 'Intern'])->default('Full-Time');
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
