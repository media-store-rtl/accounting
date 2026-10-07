<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('fiscal_year_id')->constrained('orders')->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->after('order_id')->constrained('order_items')->nullOnDelete();
            $table->index(['company_id', 'order_id']);
            $table->index(['company_id', 'order_item_id']);
        });
    }

    public function down(): void
    {
        Schema::table('productions', function (Blueprint $table) {
            $table->dropForeign(['order_item_id']);
            $table->dropForeign(['order_id']);
            $table->dropIndex(['company_id', 'order_id']);
            $table->dropIndex(['company_id', 'order_item_id']);
            $table->dropColumn(['order_item_id', 'order_id']);
        });
    }
};