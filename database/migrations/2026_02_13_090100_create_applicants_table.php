<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('position_id')->nullable();

            // Encrypted PII (Laravel encrypted casts) must be TEXT.
            $table->text('first_name');
            $table->text('middle_name')->nullable();
            $table->text('last_name');
            $table->string('suffix')->nullable();
            $table->text('email')->nullable();
            $table->text('mobile_number')->nullable();

            $table->string('source')->nullable();
            $table->string('stage', 20)->default('APPLIED');
            $table->decimal('expected_salary', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->date('applied_at')->nullable();
            $table->dateTime('last_activity_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('stage');
            $table->index('position_id');
            $table->index('last_activity_at');

            $table->foreign('position_id')
                ->references('id')
                ->on('recruitment_positions')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
