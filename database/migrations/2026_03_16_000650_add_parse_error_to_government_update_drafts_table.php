<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('government_update_drafts', function (Blueprint $table) {
            $table->longText('parse_error')->nullable()->after('parsed_payload');
        });
    }

    public function down(): void
    {
        Schema::table('government_update_drafts', function (Blueprint $table) {
            $table->dropColumn('parse_error');
        });
    }
};
