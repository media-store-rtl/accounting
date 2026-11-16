<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration {
    private const TABLE = 'production_shift_report_material_costs';

    private const REQUIRED_COLUMNS = [
        'id', 'company_id', 'production_shift_report_id', 'goods_id',
        'quantity', 'valued_quantity', 'unit_cost', 'total_cost',
        'source_cost_layer_id', 'refreshed_by', 'refreshed_at', 'created_at', 'updated_at',
    ];

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('production_shift_report_id');
                $table->unsignedBigInteger('goods_id');
                $table->decimal('quantity', 16, 4);
                $table->decimal('valued_quantity', 16, 4)->default(0);
                $table->decimal('unit_cost', 20, 4)->nullable();
                $table->decimal('total_cost', 20, 4)->nullable();
                $table->unsignedBigInteger('source_cost_layer_id')->nullable();
                $table->unsignedBigInteger('refreshed_by')->nullable();
                $table->dateTime('refreshed_at')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'production_shift_report_id', 'goods_id'], 'shift_report_material_cost_unique');
                $table->index(['company_id', 'production_shift_report_id'], 'shift_report_material_cost_lookup_idx');
            });
            return;
        }

        if (! Schema::hasColumns(self::TABLE, self::REQUIRED_COLUMNS)) {
            throw new RuntimeException('Existing '.self::TABLE.' table does not match the required migration columns; refusing to mark this migration as complete.');
        }

        if (! Schema::hasIndex(self::TABLE, 'shift_report_material_cost_unique')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->unique(['company_id', 'production_shift_report_id', 'goods_id'], 'shift_report_material_cost_unique');
            });
        }

        if (! Schema::hasIndex(self::TABLE, 'shift_report_material_cost_lookup_idx')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->index(['company_id', 'production_shift_report_id'], 'shift_report_material_cost_lookup_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists(self::TABLE);
    }
};
