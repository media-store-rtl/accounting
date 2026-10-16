<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Purchases created without a supplier must be assigned one before rollback.
        if (Schema::hasTable('purchases') && \Illuminate\Support\Facades\DB::table('purchases')->whereNull('supplier_id')->exists()) {
            throw new \RuntimeException('Cannot make purchases.supplier_id required while purchases without suppliers exist.');
        }

        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable(false)->change();
        });
    }
};
