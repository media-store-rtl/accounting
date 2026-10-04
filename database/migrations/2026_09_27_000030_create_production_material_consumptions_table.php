<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_material_consumptions', function (Blueprint $table) {
            $table->id(); $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_stage_run_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('production_operation_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('material_id')->constrained()->restrictOnDelete(); $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_movement_id')->constrained()->restrictOnDelete(); $table->decimal('quantity',18,4);
            $table->decimal('unit_cost',20,4); $table->decimal('total_cost',20,4); $table->timestamp('consumed_at'); $table->text('notes')->nullable(); $table->timestamps();
            $table->index(['production_order_id','consumed_at']); $table->index(['production_stage_run_id','production_operation_run_id']); $table->index(['material_id','warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_material_consumptions');
    }
};
