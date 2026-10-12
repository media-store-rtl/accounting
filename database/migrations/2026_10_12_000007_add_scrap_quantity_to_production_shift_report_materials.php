<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('production_shift_report_materials') && !Schema::hasColumn('production_shift_report_materials', 'scrap_quantity')) {
            Schema::table('production_shift_report_materials', function (Blueprint $table) {
                $table->decimal('scrap_quantity', 18, 4)->default(0)->after('quantity');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('production_shift_report_materials', 'scrap_quantity')) {
            Schema::table('production_shift_report_materials', function (Blueprint $table) {
                $table->dropColumn('scrap_quantity');
            });
        }
    }
};
