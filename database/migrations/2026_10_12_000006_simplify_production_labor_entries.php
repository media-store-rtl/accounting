<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('production_labor_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('production_id')->nullable()->change();
            $table->unsignedBigInteger('production_stage_id')->nullable()->change();
            $table->unsignedBigInteger('production_operation_run_id')->nullable()->change();
            $table->foreignId('production_section_id')->nullable()->after('production_operation_run_id')->constrained('production_sections')->restrictOnDelete();
            $table->foreignId('goods_id')->nullable()->after('production_section_id')->constrained('goods')->restrictOnDelete();
            $table->date('shift_date')->nullable()->after('worked_at');
            $table->string('shift_name', 80)->nullable()->after('shift_date');
            $table->decimal('hours_worked', 18, 4)->default(0)->after('shift_name');
            $table->decimal('quantity_completed', 18, 4)->default(0)->after('hours_worked');
            $table->string('pay_basis', 20)->nullable()->after('quantity_completed');
        });

        Schema::table('production_labor_costs', function (Blueprint $table) {
            $table->unsignedBigInteger('production_id')->nullable()->change();
            $table->unsignedBigInteger('production_stage_id')->nullable()->change();
            $table->unsignedBigInteger('production_operation_run_id')->nullable()->change();
            $table->foreignId('production_section_id')->nullable()->after('production_operation_run_id')->constrained('production_sections')->restrictOnDelete();
            $table->foreignId('goods_id')->nullable()->after('production_section_id')->constrained('goods')->restrictOnDelete();
            $table->date('shift_date')->nullable()->after('worked_at');
            $table->string('shift_name', 80)->nullable()->after('shift_date');
            $table->decimal('hours_worked', 18, 4)->default(0)->after('shift_name');
            $table->decimal('quantity_completed', 18, 4)->default(0)->after('hours_worked');
            $table->string('pay_basis', 20)->nullable()->after('quantity_completed');
        });

        Schema::table('production_labor_entries', function (Blueprint $table) {
            $table->index(['company_id', 'shift_date', 'personnel_id'], 'ple_company_shift_personnel_idx');
            $table->index(['company_id', 'production_section_id', 'shift_date'], 'ple_company_section_shift_idx');
        });
    }

    public function down(): void
    {
        Schema::table('production_labor_entries', function (Blueprint $table) {
            $table->dropIndex('ple_company_shift_personnel_idx');
            $table->dropIndex('ple_company_section_shift_idx');
            $table->dropForeign(['production_section_id']);
            $table->dropForeign(['goods_id']);
            $table->dropColumn(['production_section_id', 'goods_id', 'shift_date', 'shift_name', 'hours_worked', 'quantity_completed', 'pay_basis']);
        });

        Schema::table('production_labor_costs', function (Blueprint $table) {
            $table->dropForeign(['production_section_id']);
            $table->dropForeign(['goods_id']);
            $table->dropColumn(['production_section_id', 'goods_id', 'shift_date', 'shift_name', 'hours_worked', 'quantity_completed', 'pay_basis']);
        });
        // Legacy production links intentionally remain nullable so shift-based records are not destroyed.
    }
};
