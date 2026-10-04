<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_routes', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('code',100); $table->string('name'); $table->string('version',50); $table->string('status',30)->default('draft');
            $table->boolean('is_default')->default(false); $table->date('effective_from')->nullable(); $table->date('effective_to')->nullable();
            $table->text('description')->nullable(); $table->timestamps(); $table->unique(['company_id','code','version']); $table->index(['product_id','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_routes');
    }
};
