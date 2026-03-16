<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('government_source_monitors', function (Blueprint $table) {
            $table->id();

            $table->string('source_type', 32);
            $table->string('source_url');

            $table->dateTime('last_checked_at')->nullable();
            $table->string('last_hash', 128)->nullable();
            $table->string('last_status', 32)->nullable();
            $table->longText('last_error')->nullable();
            $table->string('raw_snapshot_path')->nullable();

            $table->timestamps();

            $table->unique(['source_type'], 'gsm_source_type_uniq');
            $table->index(['last_status'], 'gsm_status_idx');
            $table->index(['last_checked_at'], 'gsm_checked_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('government_source_monitors');
    }
};
