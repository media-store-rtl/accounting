<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('operation_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_operation_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goods_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_movement_id');
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 4);
            $table->decimal('total_cost', 18, 4);
            $table->timestamp('consumed_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('inventory_movement_id');
            $table->index(['goods_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_inputs');
    }
};