<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fiscal_year_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('material_id')->constrained()->restrictOnDelete(); $table->string('type',40);
            $table->decimal('quantity',18,4); $table->decimal('unit_cost',18,4)->nullable(); $table->decimal('total_cost',18,4)->nullable();
            $table->string('reference_type',100)->nullable(); $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('occurred_at'); $table->text('description')->nullable(); $table->timestamps();
            $table->index(['company_id','material_id','warehouse_id','occurred_at'],'inventory_movements_company_material_warehouse_occurred_idx');
            $table->index(['reference_type','reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
