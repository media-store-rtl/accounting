<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('personnel', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('code');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('job_title')->nullable()->after('national_id');
            $table->string('mobile', 50)->nullable()->after('phone');
            $table->string('email')->nullable()->after('mobile');
        });
    }

    public function down(): void
    {
        Schema::table('personnel', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'job_title', 'mobile', 'email']);
        });
    }
};