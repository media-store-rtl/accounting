<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('supervisor_personnel_id')->nullable()->constrained('personnel')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_sections');
    }
};