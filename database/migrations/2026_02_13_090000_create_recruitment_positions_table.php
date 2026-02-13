<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_positions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->string('status', 10)->default('OPEN');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('status');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_positions');
    }
};
