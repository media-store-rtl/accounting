<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_stages', function (Blueprint $table) {
            $table->id(); $table->foreignId('production_route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_stage_id')->nullable()->constrained('production_stages')->nullOnDelete(); $table->string('code',100); $table->string('name');
            $table->text('description')->nullable(); $table->unsignedInteger('sequence')->default(1); $table->string('status',30)->default('active');
            $table->json('attributes')->nullable(); $table->timestamps(); $table->unique(['production_route_id','code']);
            $table->index(['production_route_id','parent_stage_id','sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_stages');
    }
};
