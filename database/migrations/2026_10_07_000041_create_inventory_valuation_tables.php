<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('inventory_valuation_method', 30)->default('fifo')->after('settings');
        });

        Schema::create('inventory_cost_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('goods_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('purchase_id')->constrained('purchases')->restrictOnDelete();
            $table->foreignId('purchase_item_id')->constrained('purchase_items')->restrictOnDelete();
            $table->foreignId('purchase_receipt_id')->constrained('purchase_receipts')->restrictOnDelete();
            $table->foreignId('purchase_receipt_item_id')->constrained('purchase_receipt_items')->restrictOnDelete();
            $table->decimal('quantity_received', 18, 4);
            $table->decimal('quantity_remaining', 18, 4);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('total_cost', 20, 4);
            $table->dateTime('received_at');
            $table->timestamps();
            $table->index(['company_id', 'goods_id', 'location_id', 'received_at']);
            $table->index(['goods_id', 'location_id', 'quantity_remaining']);
            $table->unique('purchase_receipt_item_id');
        });

        Schema::create('inventory_consumption_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('goods_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $table->foreignId('inventory_movement_id')->constrained('inventory_movements')->restrictOnDelete();
            $table->string('valuation_method', 30);
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('total_cost', 20, 4);
            $table->dateTime('consumed_at');
            $table->timestamps();
            $table->unique('inventory_movement_id');
            $table->index(['company_id', 'goods_id', 'consumed_at']);
        });

        Schema::create('inventory_consumption_cost_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_consumption_cost_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_cost_layer_id')->constrained('inventory_cost_layers')->restrictOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 20, 6);
            $table->decimal('total_cost', 20, 4);
            $table->timestamps();
            $table->index('inventory_cost_layer_id');
        });
    }
};
