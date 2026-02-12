<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->char('first_name_bi', 64)->nullable()->index();
            $table->char('last_name_bi', 64)->nullable()->index();
            $table->json('first_name_prefix_bi')->nullable();
            $table->json('last_name_prefix_bi')->nullable();

            $table->string('photo_path')->nullable();
            $table->string('photo_original_name')->nullable();
            $table->string('photo_mime_type')->nullable();
            $table->unsignedBigInteger('photo_size')->nullable();

            $table->boolean('photo_is_encrypted')->default(false);
            $table->string('photo_encryption_algo')->nullable();
            $table->text('photo_encryption_iv')->nullable();
            $table->text('photo_encryption_tag')->nullable();
            $table->string('photo_key_version')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['first_name_bi']);
            $table->dropIndex(['last_name_bi']);

            $table->dropColumn([
                'first_name_bi',
                'last_name_bi',
                'first_name_prefix_bi',
                'last_name_prefix_bi',
                'photo_path',
                'photo_original_name',
                'photo_mime_type',
                'photo_size',
                'photo_is_encrypted',
                'photo_encryption_algo',
                'photo_encryption_iv',
                'photo_encryption_tag',
                'photo_key_version',
            ]);
        });
    }
};
