<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memo_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id')->nullable();

            $table->string('name');
            $table->string('slug');
            $table->string('description')->nullable();
            $table->longText('body_html');

            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);

            $table->unsignedBigInteger('created_by_user_id')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'is_system']);
            $table->unique(['company_id', 'slug']);

            $table->foreign('created_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memo_templates');
    }
};
