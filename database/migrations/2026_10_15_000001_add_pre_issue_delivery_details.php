<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_requests', function (Blueprint $table) {
            $table->string('vehicle_plate', 30)->nullable();
            $table->string('driver_phone', 30)->nullable();
            $table->text('handover_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_requests', function (Blueprint $table) {
            $table->dropColumn(['vehicle_plate', 'driver_phone', 'handover_note']);
        });
    }
};
