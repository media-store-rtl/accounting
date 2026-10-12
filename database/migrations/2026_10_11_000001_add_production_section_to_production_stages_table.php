<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // The earlier production-execution migration already adds this column
        // on a fresh database. Keep this migration safe for databases where it
        // is already present instead of failing the entire migration chain.
        if (!Schema::hasColumn('production_stages', 'production_section_id')) {
            Schema::table('production_stages', function (Blueprint $table) {
                $table->foreignId('production_section_id')->nullable()->after('production_route_id')->constrained('production_sections')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // production_section_id is owned by the earlier
        // add_production_execution_traceability migration. Do not drop it here.
    }
};
