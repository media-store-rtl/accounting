<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('locations')
            ->whereIn('type', ['warehouse', 'انبار', 'مواد'])
            ->update(['type' => 'مواد اولیه']);
    }

    public function down(): void
    {
        // Location types are normalized to the current supported values.
        // Reverting would reintroduce deprecated values without knowing their
        // original intended type.
    }
};
