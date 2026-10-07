<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', fn (Blueprint $table) => $table->unique('account_id', 'companies_account_id_unique'));
    }

    public function down(): void
    {
        Schema::table('companies', fn (Blueprint $table) => $table->dropUnique('companies_account_id_unique'));
    }
};
