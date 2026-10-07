<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('trade_name')->nullable()->after('name');
            $table->string('type', 50)->nullable()->after('trade_name');
            $table->string('national_id', 50)->nullable()->after('type');
            $table->string('registration_number', 50)->nullable()->after('national_id');
            $table->string('economic_code', 50)->nullable()->after('registration_number');
            $table->string('phone', 50)->nullable()->after('economic_code');
            $table->string('mobile', 50)->nullable()->after('phone');
            $table->string('email')->nullable()->after('mobile');
            $table->string('website')->nullable()->after('email');
            $table->string('province', 100)->nullable()->after('website');
            $table->string('city', 100)->nullable()->after('province');
            $table->text('address')->nullable()->after('city');
            $table->string('postal_code', 30)->nullable()->after('address');
            $table->string('logo_path')->nullable()->after('postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'trade_name', 'type', 'national_id', 'registration_number',
                'economic_code', 'phone', 'mobile', 'email', 'website',
                'province', 'city', 'address', 'postal_code', 'logo_path',
            ]);
        });
    }
};
