<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('goods_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            $table->decimal('conversion_factor', 18, 6);
            $table->boolean('is_base')->default(false);
            $table->timestamps();
            $table->unique(['goods_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_units');
    }
};