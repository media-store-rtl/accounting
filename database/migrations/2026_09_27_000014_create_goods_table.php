<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('goods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('goods_categories')->restrictOnDelete();
            $table->string('code', 100);
            $table->string('name');
            $table->boolean('purchasable')->default(false);
            $table->boolean('producible')->default(false);
            $table->boolean('sellable')->default(false);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('attributes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods');
    }
};