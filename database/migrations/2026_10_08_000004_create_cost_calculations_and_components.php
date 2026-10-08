<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('cost_calculations')) {
            Schema::create('cost_calculations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('goods_id')->nullable()->constrained()->nullOnDelete();
                $table->string('status', 30)->default('calculated');
                $table->decimal('material_cost', 20, 4)->default(0);
                $table->decimal('labor_cost', 20, 4)->default(0);
                $table->decimal('scrap_cost', 20, 4)->default(0);
                $table->decimal('direct_cost', 20, 4)->default(0);
                $table->decimal('total_cost', 20, 4)->default(0);
                $table->timestamp('calculated_at');
                $table->foreignId('calculated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['company_id','fiscal_year_id','order_id','goods_id']);
            });
        }

        if (!Schema::hasTable('cost_components')) {
            Schema::create('cost_components', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cost_calculation_id')->constrained('cost_calculations')->cascadeOnDelete();
                $table->string('component_type', 40);
                $table->string('source_type', 80)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->decimal('amount', 20, 4);
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['cost_calculation_id','component_type']);
                $table->index(['source_type','source_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_components');
        Schema::dropIfExists('cost_calculations');
    }
};