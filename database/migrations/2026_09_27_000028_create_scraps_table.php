<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scraps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_operation_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('goods_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_cost', 18, 4);
            $table->decimal('total_cost', 18, 4);
            $table->string('reason', 255)->nullable();
            $table->timestamp('scrapped_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['goods_id', 'scrapped_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scraps');
    }
};