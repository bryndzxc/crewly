<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crewly_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('type'); // e.g. DOC_EXPIRING, PROBATION_ENDING, LEAVE_PENDING, INCIDENT_FOLLOWUP
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->string('severity'); // INFO, WARNING, DANGER, SUCCESS
            $table->json('data')->nullable();

            $table->string('dedupe_key', 64)->nullable();

            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
            $table->index('read_at');
            $table->index('created_at');
            $table->unique(['user_id', 'dedupe_key'], 'crewly_notifications_user_dedupe_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crewly_notifications');
    }
};
