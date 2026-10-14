<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('production_shift_reports') && !Schema::hasColumn('production_shift_reports', 'warehouse_receipt_requested')) {
            Schema::table('production_shift_reports', function (Blueprint $table) {
                $table->boolean('warehouse_receipt_requested')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('production_shift_reports') && Schema::hasColumn('production_shift_reports', 'warehouse_receipt_requested')) {
            Schema::table('production_shift_reports', function (Blueprint $table) {
                $table->dropColumn('warehouse_receipt_requested');
            });
        }
    }
};
