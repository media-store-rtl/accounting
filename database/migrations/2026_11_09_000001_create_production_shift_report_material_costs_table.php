<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_shift_report_material_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('production_shift_report_id');
            $table->unsignedBigInteger('goods_id');
            $table->decimal('quantity', 16, 4);
            $table->decimal('valued_quantity', 16, 4)->default(0);
            $table->decimal('unit_cost', 20, 4)->nullable();
            $table->decimal('total_cost', 20, 4)->nullable();
            $table->unsignedBigInteger('source_cost_layer_id')->nullable();
            $table->unsignedBigInteger('refreshed_by')->nullable();
            $table->dateTime('refreshed_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'production_shift_report_id', 'goods_id'], 'shift_report_material_cost_unique');
            $table->index(['company_id', 'production_shift_report_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_shift_report_material_costs');
    }
};
