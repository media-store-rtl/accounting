<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('personnel_production_sections', function (Blueprint $table) {
            $table->foreignId('personnel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_section_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['personnel_id', 'production_section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_production_sections');
    }
};