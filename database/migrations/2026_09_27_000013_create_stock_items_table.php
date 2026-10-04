<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->string('item_type',30);
            $table->foreignId('material_id')->nullable()->constrained()->restrictOnDelete(); $table->foreignId('product_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('code',100); $table->string('name'); $table->string('unit',30); $table->boolean('is_active')->default(true);
            $table->json('attributes')->nullable(); $table->timestamps(); $table->unique(['company_id','code']);
            $table->unique(['company_id','material_id']); $table->unique(['company_id','product_id']); $table->index(['company_id','item_type','is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_items');
    }
};
