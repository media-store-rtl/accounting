<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('supply_requests', 'production_stage_run_id')) {
            Schema::table('supply_requests', function (Blueprint $table) {
                $table->foreignId('production_stage_run_id')
                    ->nullable()
                    ->after('production_id')
                    ->constrained('production_stage_runs')
                    ->nullOnDelete();
                $table->index(['company_id', 'production_stage_run_id'], 'supply_requests_company_stage_run_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('supply_requests', 'production_stage_run_id')) {
            Schema::table('supply_requests', function (Blueprint $table) {
                $table->dropIndex('supply_requests_company_stage_run_idx');
                $table->dropConstrainedForeignId('production_stage_run_id');
            });
        }
    }
};
