<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'status')) {
                $table->string('status')->default('pending')->after('source_page');
                $table->index('status', 'leads_status_idx');
            }

            if (!Schema::hasColumn('leads', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('leads', 'declined_at')) {
                $table->timestamp('declined_at')->nullable()->after('approved_at');
            }

            if (!Schema::hasColumn('leads', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('declined_at');
                $table->index('company_id', 'leads_company_id_idx');
            }

            if (!Schema::hasColumn('leads', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('company_id');
                $table->index('user_id', 'leads_user_id_idx');
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'company_id') && Schema::hasTable('companies')) {
                $table->foreign('company_id', 'leads_company_id_fk')
                    ->references('id')
                    ->on('companies')
                    ->nullOnDelete();
            }

            if (Schema::hasColumn('leads', 'user_id') && Schema::hasTable('users')) {
                $table->foreign('user_id', 'leads_user_id_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'company_id') && Schema::hasTable('companies')) {
                $table->dropForeign('leads_company_id_fk');
            }

            if (Schema::hasColumn('leads', 'user_id') && Schema::hasTable('users')) {
                $table->dropForeign('leads_user_id_fk');
            }

            if (Schema::hasColumn('leads', 'status')) {
                $table->dropIndex('leads_status_idx');
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('leads', 'approved_at')) {
                $table->dropColumn('approved_at');
            }

            if (Schema::hasColumn('leads', 'declined_at')) {
                $table->dropColumn('declined_at');
            }

            if (Schema::hasColumn('leads', 'company_id')) {
                $table->dropIndex('leads_company_id_idx');
                $table->dropColumn('company_id');
            }

            if (Schema::hasColumn('leads', 'user_id')) {
                $table->dropIndex('leads_user_id_idx');
                $table->dropColumn('user_id');
            }
        });
    }
};
