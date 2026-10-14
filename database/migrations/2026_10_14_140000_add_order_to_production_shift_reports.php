<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('production_shift_reports', 'order_id')) {
            Schema::table('production_shift_reports', function (Blueprint $table) {
                $table->foreignId('order_id')->nullable()->after('goods_id')->constrained('orders')->nullOnDelete();
                $table->index(['company_id', 'order_id', 'status'], 'psr_company_order_status_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('production_shift_reports', 'order_id')) {
            Schema::table('production_shift_reports', function (Blueprint $table) {
                $table->dropIndex('psr_company_order_status_idx');
                $table->dropConstrainedForeignId('order_id');
            });
        }
    }
};
