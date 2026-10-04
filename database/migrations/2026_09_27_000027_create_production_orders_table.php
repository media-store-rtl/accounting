<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete(); $table->foreignId('product_structure_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('production_route_id')->nullable()->constrained()->restrictOnDelete(); $table->foreignId('parent_production_order_id')->nullable()->constrained('production_orders')->nullOnDelete();
            $table->string('number',100); $table->decimal('planned_quantity',18,4); $table->decimal('produced_quantity',18,4)->default(0);
            $table->decimal('rejected_quantity',18,4)->default(0); $table->string('status',30)->default('draft');
            $table->timestamp('planned_start_at')->nullable(); $table->timestamp('planned_end_at')->nullable(); $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable(); $table->text('notes')->nullable(); $table->timestamps();
            $table->unique(['company_id','number']); $table->index(['company_id','status']); $table->index('parent_production_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
