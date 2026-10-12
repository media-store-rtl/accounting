<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('production_shift_reports', 'worked_hours')) {
            Schema::table('production_shift_reports', function (Blueprint $table) {
                $table->decimal('worked_hours', 18, 4)->nullable()->after('work_date');
                $table->string('labor_rate_type', 20)->nullable()->after('worked_hours');
                $table->decimal('labor_unit_rate', 20, 6)->nullable()->after('labor_rate_type');
                $table->decimal('labor_total_cost', 20, 4)->default(0)->after('labor_unit_rate');
                $table->timestamp('labor_cost_calculated_at')->nullable()->after('labor_total_cost');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('production_shift_reports', 'worked_hours')) {
            Schema::table('production_shift_reports', function (Blueprint $table) {
                $table->dropColumn([
                    'worked_hours',
                    'labor_rate_type',
                    'labor_unit_rate',
                    'labor_total_cost',
                    'labor_cost_calculated_at',
                ]);
            });
        }
    }
};
