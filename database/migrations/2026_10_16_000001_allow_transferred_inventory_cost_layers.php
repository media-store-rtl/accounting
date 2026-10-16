<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // One approved receipt item can now have multiple location-specific layer
        // slices after partial transfers to production sections.
        Schema::table('inventory_cost_layers', function (Blueprint $table) {
            $table->dropUnique('inventory_cost_layers_purchase_receipt_item_id_unique');
            $table->index('purchase_receipt_item_id', 'icl_receipt_item_idx');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_cost_layers', function (Blueprint $table) {
            $table->dropIndex('icl_receipt_item_idx');
        });
        // Do not restore uniqueness: a partial transfer legitimately splits one
        // receipt item's cost across more than one location.
    }
};
