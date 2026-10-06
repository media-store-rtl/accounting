<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fiscal_years', function (Blueprint $table) {
            $table->foreignId('subscription_entitlement_id')
                ->nullable()
                ->after('company_id')
                ->constrained('subscription_entitlements')
                ->nullOnDelete();

            $table->unique(
                ['company_id', 'subscription_entitlement_id'],
                'fiscal_years_company_subscription_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('fiscal_years', function (Blueprint $table) {
            $table->dropUnique('fiscal_years_company_subscription_unique');
            $table->dropConstrainedForeignId('subscription_entitlement_id');
        });
    }
};
