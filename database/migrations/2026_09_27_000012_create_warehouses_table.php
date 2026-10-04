<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id(); $table->foreignId('company_id')->constrained()->cascadeOnDelete(); $table->string('code',50); $table->string('name');
            $table->string('type',30)->default('raw_material'); $table->boolean('is_active')->default(true); $table->timestamps();
            $table->unique(['company_id','code']); $table->index(['company_id','is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
