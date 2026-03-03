<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('plan_name')->default('starter')->after('is_demo');
            $table->unsignedInteger('max_employees')->default(20)->after('plan_name');

            $table->string('subscription_status')->default('trial')->after('max_employees');
            $table->dateTime('trial_ends_at')->nullable()->after('subscription_status');
            $table->dateTime('next_billing_at')->nullable()->after('trial_ends_at');
            $table->dateTime('last_payment_at')->nullable()->after('next_billing_at');

            $table->unsignedInteger('grace_days')->default(7)->after('last_payment_at');
            $table->text('billing_notes')->nullable()->after('grace_days');

            $table->index(['subscription_status', 'next_billing_at'], 'companies_subscription_status_next_billing_idx');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex('companies_subscription_status_next_billing_idx');

            $table->dropColumn([
                'plan_name',
                'max_employees',
                'subscription_status',
                'trial_ends_at',
                'next_billing_at',
                'last_payment_at',
                'grace_days',
                'billing_notes',
            ]);
        });
    }
};
