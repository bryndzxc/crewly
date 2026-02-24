<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('company_id')->nullable();

            $table->unsignedInteger('employee_id');
            $table->unsignedBigInteger('incident_id')->nullable();
            $table->unsignedBigInteger('memo_template_id')->nullable();

            $table->string('title');
            $table->longText('body_rendered_html');
            $table->string('pdf_path');
            $table->string('status')->default('generated');

            $table->unsignedBigInteger('created_by_user_id');

            $table->timestamps();

            $table->index(['employee_id', 'created_at']);
            $table->index(['incident_id', 'created_at']);
            $table->index(['memo_template_id', 'created_at']);

            $table->foreign('employee_id')
                ->references('employee_id')
                ->on('employees')
                ->onDelete('cascade');

            $table->foreign('incident_id')
                ->references('id')
                ->on('employee_incidents')
                ->nullOnDelete();

            $table->foreign('memo_template_id')
                ->references('id')
                ->on('memo_templates')
                ->nullOnDelete();

            $table->foreign('created_by_user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memos');
    }
};
