<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_requests', function (Blueprint $table) {
            $table->string('recipient_code', 100)->nullable()->after('recipient_name');
            $table->string('driver_name')->nullable()->after('recipient_code');
            $table->string('waybill_number', 100)->nullable()->after('driver_name');
            $table->string('vehicle_number', 100)->nullable()->after('waybill_number');
            $table->string('vehicle_type', 100)->nullable()->after('vehicle_number');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_requests', function (Blueprint $table) {
            $table->dropColumn([
                'recipient_code',
                'driver_name',
                'waybill_number',
                'vehicle_number',
                'vehicle_type',
            ]);
        });
    }
};
