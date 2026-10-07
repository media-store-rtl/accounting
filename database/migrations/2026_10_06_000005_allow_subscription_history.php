<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscription_entitlements', function (Blueprint $table) {
            $table->dropUnique('subscription_entitlements_company_id_unique');
            $table->index(['company_id', 'external_subscription_id']);
        });
    }

    public function down(): void
    {
        Schema::table('subscription_entitlements', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'external_subscription_id']);
            $table->unique('company_id');
        });
    }
};
