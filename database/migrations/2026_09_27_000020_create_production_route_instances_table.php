<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_route_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('production_route_id')->constrained()->restrictOnDelete();
            $table->json('snapshot');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_route_instances');
    }
};