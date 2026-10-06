<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('owner_user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('password');
            $table->index(['account_id', 'is_active']);
        });

        Schema::table('subscription_entitlements', function (Blueprint $table) {
            $table->unsignedInteger('max_users')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_entitlements', function (Blueprint $table) {
            $table->dropColumn('max_users');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['account_id', 'is_active']);
            $table->dropColumn('is_active');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_user_id');
        });
    }
};