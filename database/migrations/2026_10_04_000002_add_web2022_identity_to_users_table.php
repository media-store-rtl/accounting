<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('web2022_user_id')->nullable()->unique()->after('id');
            $table->uuid('web2022_subscription_id')->nullable()->unique()->after('web2022_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['web2022_subscription_id']);
            $table->dropUnique(['web2022_user_id']);
            $table->dropColumn(['web2022_subscription_id', 'web2022_user_id']);
        });
    }
};
