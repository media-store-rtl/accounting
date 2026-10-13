<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_shift_report_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('production_shift_report_id')->constrained('production_shift_reports')->cascadeOnDelete();
            $table->foreignId('production_stage_id')->constrained('production_stages')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['production_shift_report_id', 'production_stage_id'], 'psrs_report_stage_unique');
            $table->index(['company_id', 'production_shift_report_id'], 'psrs_company_report_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_shift_report_stages');
    }
};
