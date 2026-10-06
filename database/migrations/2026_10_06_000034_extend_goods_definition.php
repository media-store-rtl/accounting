<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('goods', function (Blueprint $table) {
            $table->string('item_type', 30)->default('product')->after('name');
            $table->string('product_type', 30)->nullable()->after('item_type');
            $table->index(['company_id', 'item_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('goods', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'item_type', 'is_active']);
            $table->dropColumn(['item_type', 'product_type']);
        });
    }
};
