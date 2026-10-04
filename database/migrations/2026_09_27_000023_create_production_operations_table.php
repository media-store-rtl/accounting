<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_operations', function (Blueprint $table) {
            $table->id(); $table->foreignId('production_stage_id')->constrained()->cascadeOnDelete(); $table->string('code',100); $table->string('name');
            $table->text('description')->nullable(); $table->unsignedInteger('sequence')->default(1); $table->unsignedInteger('standard_duration_minutes')->nullable();
            $table->unsignedInteger('setup_duration_minutes')->nullable(); $table->string('status',30)->default('active'); $table->json('attributes')->nullable();
            $table->timestamps(); $table->unique(['production_stage_id','code']); $table->index(['production_stage_id','sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_operations');
    }
};
