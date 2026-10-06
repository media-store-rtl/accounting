<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $t) {
            $t->id(); $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('fiscal_year_id')->constrained()->restrictOnDelete(); $t->foreignId('customer_id')->constrained()->restrictOnDelete();
            $t->string('number',100); $t->date('ordered_at'); $t->date('requested_delivery_at'); $t->date('production_due_at')->nullable();
            $t->string('status',40)->default('draft'); $t->text('notes')->nullable(); $t->timestamps();
            $t->unique(['company_id','number']); $t->index(['company_id','status']);
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('order_id')->constrained()->cascadeOnDelete(); $t->foreignId('goods_id')->constrained()->restrictOnDelete();
            $t->decimal('quantity',18,4); $t->decimal('available_quantity',18,4)->default(0); $t->decimal('shortage_quantity',18,4)->default(0);
            $t->string('fulfillment_status',30)->default('pending'); $t->timestamps(); $t->unique(['order_id','goods_id']);
        });
        Schema::create('inventory', function (Blueprint $t) {
            $t->id(); $t->foreignId('company_id')->constrained()->cascadeOnDelete(); $t->foreignId('location_id')->constrained('locations')->restrictOnDelete();
            $t->foreignId('goods_id')->constrained()->restrictOnDelete(); $t->decimal('quantity',18,4)->default(0); $t->timestamps();
            $t->unique(['location_id','goods_id']); $t->index(['company_id','goods_id']);
        });
        Schema::create('inventory_movements', function (Blueprint $t) {
            $t->id(); $t->foreignId('company_id')->constrained()->cascadeOnDelete(); $t->foreignId('goods_id')->constrained()->restrictOnDelete();
            $t->foreignId('location_id')->constrained('locations')->restrictOnDelete(); $t->decimal('quantity',18,4); $t->string('movement_type',40);
            $t->nullableMorphs('reference'); $t->timestamp('occurred_at'); $t->json('metadata')->nullable(); $t->timestamps();
            $t->index(['company_id','goods_id','occurred_at']);
        });
        Schema::create('supplier_goods', function (Blueprint $t) {
            $t->foreignId('supplier_id')->constrained()->cascadeOnDelete(); $t->foreignId('goods_id')->constrained()->cascadeOnDelete(); $t->timestamps();
            $t->primary(['supplier_id','goods_id']);
        });
        Schema::create('supply_requests', function (Blueprint $t) {
            $t->id(); $t->foreignId('company_id')->constrained()->cascadeOnDelete(); $t->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
            $t->foreignId('production_id')->nullable()->constrained()->nullOnDelete(); $t->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('requested_by_user_id')->constrained('users')->restrictOnDelete(); $t->dateTime('requested_at'); $t->date('needed_at');
            $t->string('status',40)->default('draft'); $t->text('notes')->nullable(); $t->timestamps(); $t->index(['company_id','status']);
        });
        Schema::create('supply_request_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('supply_request_id')->constrained()->cascadeOnDelete(); $t->foreignId('goods_id')->constrained()->restrictOnDelete();
            $t->decimal('requested_quantity',18,4); $t->decimal('available_quantity',18,4)->default(0); $t->decimal('shortage_quantity',18,4)->default(0);
            $t->decimal('supplied_quantity',18,4)->default(0); $t->timestamps(); $t->unique(['supply_request_id','goods_id']);
        });
        Schema::create('purchases', function (Blueprint $t) {
            $t->id(); $t->foreignId('company_id')->constrained()->cascadeOnDelete(); $t->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
            $t->foreignId('supplier_id')->constrained()->restrictOnDelete(); $t->foreignId('supply_request_id')->nullable()->constrained()->nullOnDelete();
            $t->string('invoice_number',100)->nullable(); $t->date('invoice_date')->nullable(); $t->date('purchased_at');
            $t->decimal('subtotal',20,4)->default(0); $t->decimal('direct_cost_total',20,4)->default(0); $t->decimal('total_amount',20,4)->default(0);
            $t->string('status',40)->default('draft'); $t->text('notes')->nullable(); $t->timestamps(); $t->index(['company_id','status']);
        });
        Schema::create('purchase_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('purchase_id')->constrained()->cascadeOnDelete(); $t->foreignId('goods_id')->constrained()->restrictOnDelete();
            $t->decimal('quantity',18,4); $t->decimal('unit_price',20,4); $t->decimal('line_total',20,4); $t->timestamps();
            $t->unique(['purchase_id','goods_id']);
        });
        Schema::create('purchase_direct_costs', function (Blueprint $t) {
            $t->id(); $t->foreignId('purchase_id')->constrained()->cascadeOnDelete(); $t->string('type',50);
            $t->string('description')->nullable(); $t->decimal('amount',20,4); $t->timestamps(); $t->index(['purchase_id','type']);
        });
        Schema::create('purchase_receipts', function (Blueprint $t) {
            $t->id(); $t->foreignId('company_id')->constrained()->cascadeOnDelete(); $t->foreignId('purchase_id')->constrained()->restrictOnDelete();
            $t->foreignId('warehouse_location_id')->constrained('locations')->restrictOnDelete(); $t->foreignId('received_by_user_id')->constrained('users')->restrictOnDelete();
            $t->dateTime('received_at'); $t->dateTime('approved_at')->nullable(); $t->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('status',30)->default('pending'); $t->text('notes')->nullable(); $t->timestamps(); $t->index(['company_id','status']);
        });
        Schema::create('purchase_receipt_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('purchase_receipt_id')->constrained()->cascadeOnDelete(); $t->foreignId('goods_id')->constrained()->restrictOnDelete();
            $t->decimal('quantity',18,4); $t->timestamps(); $t->unique(['purchase_receipt_id','goods_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_receipt_items'); Schema::dropIfExists('purchase_receipts'); Schema::dropIfExists('purchase_direct_costs');
        Schema::dropIfExists('purchase_items'); Schema::dropIfExists('purchases'); Schema::dropIfExists('supply_request_items');
        Schema::dropIfExists('supply_requests'); Schema::dropIfExists('supplier_goods'); Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory'); Schema::dropIfExists('order_items'); Schema::dropIfExists('orders');
    }
};