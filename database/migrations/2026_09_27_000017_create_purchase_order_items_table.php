<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete(); $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity',18,4); $table->decimal('unit_price',18,4); $table->decimal('discount',18,4)->default(0);
            $table->decimal('tax',18,4)->default(0); $table->decimal('total',18,4); $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
