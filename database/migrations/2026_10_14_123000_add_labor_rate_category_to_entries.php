<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('production_labor_entries') && !Schema::hasColumn('production_labor_entries', 'labor_rate_type')) {
            Schema::table('production_labor_entries', function (Blueprint $table) {
                $table->string('labor_rate_type', 30)->nullable()->after('measure_type');
            });
        }

        if (Schema::hasTable('production_labor_costs') && !Schema::hasColumn('production_labor_costs', 'labor_rate_type')) {
            Schema::table('production_labor_costs', function (Blueprint $table) {
                $table->string('labor_rate_type', 30)->nullable()->after('measure_type');
            });
        }

        if (Schema::hasTable('personnel_labor_rates')) {
            DB::table('personnel_labor_rates')
                ->where('rate_type', 'hourly')
                ->update(['rate_type' => 'regular']);
        }

        if (Schema::hasTable('production_labor_entries') && Schema::hasColumn('production_labor_entries', 'labor_rate_type')) {
            DB::table('production_labor_entries')->whereNull('labor_rate_type')->where('measure_type', 'hours')->update(['labor_rate_type' => 'regular']);
            DB::table('production_labor_entries')->whereNull('labor_rate_type')->where('measure_type', 'quantity')->update(['labor_rate_type' => 'per_unit']);
        }

        if (Schema::hasTable('production_labor_costs') && Schema::hasColumn('production_labor_costs', 'labor_rate_type')) {
            DB::table('production_labor_costs')->whereNull('labor_rate_type')->where('measure_type', 'hours')->update(['labor_rate_type' => 'regular']);
            DB::table('production_labor_costs')->whereNull('labor_rate_type')->where('measure_type', 'quantity')->update(['labor_rate_type' => 'per_unit']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('production_labor_costs') && Schema::hasColumn('production_labor_costs', 'labor_rate_type')) {
            Schema::table('production_labor_costs', function (Blueprint $table) {
                $table->dropColumn('labor_rate_type');
            });
        }

        if (Schema::hasTable('production_labor_entries') && Schema::hasColumn('production_labor_entries', 'labor_rate_type')) {
            Schema::table('production_labor_entries', function (Blueprint $table) {
                $table->dropColumn('labor_rate_type');
            });
        }
    }
};
