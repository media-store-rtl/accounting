<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // One approved receipt item can now have multiple location-specific layer
        // slices after partial transfers to production sections.
        // Production databases may have drifted from the original index name, so
        // remove any single-column unique index on this field rather than assuming
        // Laravel's generated name exists.
        foreach (Schema::getIndexes('inventory_cost_layers') as $index) {
            if (
                ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['purchase_receipt_item_id']
            ) {
                Schema::table('inventory_cost_layers', function (Blueprint $table) use ($index) {
                    $table->dropUnique($index['name']);
                });
            }
        }

        $indexes = Schema::getIndexes('inventory_cost_layers');
        $hasReceiptItemIndex = collect($indexes)->contains(
            fn (array $index): bool => in_array('purchase_receipt_item_id', $index['columns'] ?? [], true)
        );

        if (! $hasReceiptItemIndex) {
            Schema::table('inventory_cost_layers', function (Blueprint $table) {
                $table->index('purchase_receipt_item_id', 'icl_receipt_item_idx');
            });
        }
    }

    public function down(): void
    {
        $hasIndex = collect(Schema::getIndexes('inventory_cost_layers'))->contains(
            fn (array $index): bool => ($index['name'] ?? null) === 'icl_receipt_item_idx'
        );

        if ($hasIndex) {
            Schema::table('inventory_cost_layers', function (Blueprint $table) {
                $table->dropIndex('icl_receipt_item_idx');
            });
        }

        // Do not restore uniqueness: a partial transfer legitimately splits one
        // receipt item's cost across more than one location.
    }
};
