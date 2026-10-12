<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('production_shift_reports', 'worked_hours')) {
            Schema::table('production_shift_reports', function (Blueprint $table) {
                $table->decimal('worked_hours', 6, 2)->default(8)->after('shift_name');
            });
        }

        if (!Schema::hasColumn('production_shift_reports', 'overtime_hours')) {
            Schema::table('production_shift_reports', function (Blueprint $table) {
                $table->decimal('overtime_hours', 6, 2)->default(0)->after('worked_hours');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('production_shift_reports', 'overtime_hours')) {
            Schema::table('production_shift_reports', function (Blueprint $table) {
                $table->dropColumn('overtime_hours');
            });
        }

        if (Schema::hasColumn('production_shift_reports', 'worked_hours')) {
            Schema::table('production_shift_reports', function (Blueprint $table) {
                $table->dropColumn('worked_hours');
            });
        }
    }
};
