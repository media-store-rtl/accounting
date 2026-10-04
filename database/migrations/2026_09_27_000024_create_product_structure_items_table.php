<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_structure_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_structure_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->restrictOnDelete(); $table->foreignId('component_product_id')->nullable()->constrained('products')->restrictOnDelete();
            $table->foreignId('production_stage_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('production_operation_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity',18,4); $table->string('unit',30); $table->decimal('scrap_percent',9,4)->default(0);
            $table->unsignedInteger('sequence')->default(1); $table->text('notes')->nullable(); $table->timestamps();
            $table->index(['product_structure_id','sequence']); $table->index(['material_id','production_stage_id']); $table->index(['component_product_id','production_stage_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_structure_items');
    }
};
