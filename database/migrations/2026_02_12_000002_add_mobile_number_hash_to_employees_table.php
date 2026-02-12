<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('mobile_number_hash', 64)->nullable()->after('mobile_number');
            $table->index(['mobile_number_hash', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['mobile_number_hash', 'deleted_at']);
            $table->dropColumn('mobile_number_hash');
        });
    }
};
