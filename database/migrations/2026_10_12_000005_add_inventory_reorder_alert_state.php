<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('inventory', 'reorder_alert_active')) {
            Schema::table('inventory', function (Blueprint $table) {
                $table->boolean('reorder_alert_active')->default(false)->after('reorder_point');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('inventory', 'reorder_alert_active')) {
            Schema::table('inventory', function (Blueprint $table) {
                $table->dropColumn('reorder_alert_active');
            });
        }
    }
};
