<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory', function (Blueprint $table) {
            $table->decimal('reorder_point', 18, 4)->default(0)->after('quantity');
            $table->index(['company_id', 'location_id', 'goods_id']);
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('performed_by')->nullable()->after('reference_id')->constrained('users')->nullOnDelete();
            $table->uuid('transfer_reference')->nullable()->after('performed_by');
            $table->index('transfer_reference');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['performed_by']);
            $table->dropIndex(['transfer_reference']);
            $table->dropColumn(['performed_by', 'transfer_reference']);
        });
        Schema::table('inventory', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'location_id', 'goods_id']);
            $table->dropColumn('reorder_point');
        });
    }
};
