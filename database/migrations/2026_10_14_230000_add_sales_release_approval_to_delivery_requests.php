<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('sales_approved_by_user_id')->nullable()->after('requested_by_user_id');
            $table->timestamp('sales_approved_at')->nullable()->after('sales_approved_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_requests', function (Blueprint $table) {
            $table->dropColumn(['sales_approved_by_user_id', 'sales_approved_at']);
        });
    }
};
