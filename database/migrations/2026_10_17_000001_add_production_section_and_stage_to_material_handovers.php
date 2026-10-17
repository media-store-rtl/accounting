<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('material_handovers', function (Blueprint $table) {
            $table->foreignId('production_section_id')->nullable()->after('warehouse_location_id')->constrained('production_sections')->nullOnDelete();
            $table->foreignId('production_stage_id')->nullable()->after('production_section_id')->constrained('production_stages')->nullOnDelete();
            $table->index(['company_id', 'production_section_id', 'production_stage_id'], 'material_handovers_production_context_idx');
        });
    }

    public function down(): void
    {
        Schema::table('material_handovers', function (Blueprint $table) {
            $table->dropIndex('material_handovers_production_context_idx');
            $table->dropConstrainedForeignId('production_stage_id');
            $table->dropConstrainedForeignId('production_section_id');
        });
    }
};
