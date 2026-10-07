<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('material_handovers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
            $t->foreignId('supply_request_id')->constrained()->restrictOnDelete();
            $t->foreignId('warehouse_location_id')->constrained('locations')->restrictOnDelete();
            $t->foreignId('delivered_by_user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('received_by_user_id')->constrained('users')->restrictOnDelete();
            $t->dateTime('handed_over_at');
            $t->string('status', 30)->default('completed');
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['company_id', 'status']);
        });

        Schema::create('material_handover_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('material_handover_id')->constrained()->cascadeOnDelete();
            $t->foreignId('goods_id')->constrained()->restrictOnDelete();
            $t->decimal('quantity', 18, 4);
            $t->timestamps();
            $t->unique(['material_handover_id', 'goods_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_handover_items');
        Schema::dropIfExists('material_handovers');
    }
};