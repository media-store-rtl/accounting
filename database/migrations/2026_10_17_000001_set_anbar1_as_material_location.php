<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('locations')
            ->where('name', 'انبار1')
            ->where('type', 'محصول')
            ->update(['type' => 'مواد']);
    }

    public function down(): void
    {
        DB::table('locations')
            ->where('name', 'انبار1')
            ->where('type', 'مواد')
            ->update(['type' => 'محصول']);
    }
};
