<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('government_update_drafts', function (Blueprint $table) {
            $table->id();

            $table->string('source_type', 32);
            $table->dateTime('detected_at');
            $table->string('source_url');
            $table->string('content_hash', 128);
            $table->string('status', 32)->default('draft');
            $table->json('parsed_payload');

            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->longText('notes')->nullable();

            $table->timestamps();

            $table->index(['source_type', 'status'], 'gud_type_status_idx');
            $table->index(['detected_at'], 'gud_detected_idx');
            $table->index(['content_hash'], 'gud_hash_idx');

            $table->foreign('reviewed_by', 'gud_reviewed_by_fk')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('government_update_drafts');
    }
};
