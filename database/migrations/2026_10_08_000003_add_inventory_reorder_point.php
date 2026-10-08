<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('inventory', 'reorder_point')) {
            Schema::table('inventory', function (Blueprint $table) {
                $table->decimal('reorder_point', 18, 4)->default(0)->after('quantity');
                $table->index(['company_id', 'location_id', 'goods_id', 'reorder_point']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventory', 'reorder_point')) {
            Schema::table('inventory', function (Blueprint $table) {
                $table->dropIndex(['company_id', 'location_id', 'goods_id', 'reorder_point']);
                $table->dropColumn('reorder_point');
            });
        }
    }
};