<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('production_shift_reports') || Schema::hasColumn('production_shift_reports', 'warehouse_requested_at')) {
            return;
        }

        Schema::table('production_shift_reports', function (Blueprint $table) {
            $table->timestamp('warehouse_requested_at')->nullable()->after('warehouse_quantity');
        });

        // Preserve visibility of already-approved final goods that were awaiting warehouse receipt.
        DB::table('production_shift_reports')
            ->whereIn('status', ['approved', 'received'])
            ->update(['warehouse_requested_at' => DB::raw('COALESCE(reviewed_at, updated_at, created_at)')]);
    }

    public function down(): void
    {
        if (Schema::hasTable('production_shift_reports') && Schema::hasColumn('production_shift_reports', 'warehouse_requested_at')) {
            Schema::table('production_shift_reports', function (Blueprint $table) {
                $table->dropColumn('warehouse_requested_at');
            });
        }
    }
};
