<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('goods', 'inventory_valuation_method')) {
            Schema::table('goods', function (Blueprint $table) {
                $table->string('inventory_valuation_method', 30)->nullable()->after('sellable');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('goods', 'inventory_valuation_method')) {
            Schema::table('goods', function (Blueprint $table) {
                $table->dropColumn('inventory_valuation_method');
            });
        }
    }
};
