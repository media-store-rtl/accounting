<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('purchase_receipts')->whereNull('purchase_id')->exists()) {
            throw new \RuntimeException('Cannot make purchase_receipts.purchase_id required while unlinked warehouse receipts exist.');
        }

        Schema::table('purchase_receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_id')->nullable(false)->change();
        });
    }
};
