<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration can run before the report table is created on a fresh install.
        // The creation migration includes holiday_hours; keep material-table creation independent.
        if (Schema::hasTable('production_shift_reports') && !Schema::hasColumn('production_shift_reports', 'holiday_hours')) {
            Schema::table('production_shift_reports', function (Blueprint $table) {
                $table->decimal('holiday_hours', 6, 2)->default(0)->after('overtime_hours');
            });
        }

        if (!Schema::hasTable('production_shift_report_materials')) {
            Schema::create('production_shift_report_materials', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('production_shift_report_id')->index();
                $table->unsignedBigInteger('goods_id')->index();
                $table->decimal('quantity', 18, 4);
                $table->timestamps();
                $table->index(['company_id', 'production_shift_report_id'], 'psrm_company_report_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('production_shift_report_materials');

        if (Schema::hasTable('production_shift_reports') && Schema::hasColumn('production_shift_reports', 'holiday_hours')) {
            Schema::table('production_shift_reports', function (Blueprint $table) {
                $table->dropColumn('holiday_hours');
            });
        }
    }
};
