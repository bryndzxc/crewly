<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_relation_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('attachable_type');
            $table->unsignedBigInteger('attachable_id');

            $table->string('type')->nullable();
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();

            $table->boolean('is_encrypted')->default(true);
            $table->string('encryption_algo')->default('AES-256-GCM');
            $table->text('encryption_iv');
            $table->text('encryption_tag');
            $table->unsignedSmallInteger('key_version')->default(1);

            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id'], 'era_attachable_idx');

            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_relation_attachments');
    }
};
