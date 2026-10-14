<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')
            ->where('slug', 'delivery_request.view')
            ->update(['module' => 'warehouse']);
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('slug', 'delivery_request.view')
            ->update(['module' => 'sales']);
    }
};
