<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach ([
            ['name' => 'View Production Outputs', 'slug' => 'production.output.view', 'module' => 'production'],
            ['name' => 'Create Production Output', 'slug' => 'production.output.create', 'module' => 'production'],
            ['name' => 'Confirm Finished Goods Receipt', 'slug' => 'production.output.confirm', 'module' => 'production'],
            ['name' => 'Reject Production Output', 'slug' => 'production.output.reject', 'module' => 'production'],
        ] as $permission) {
            DB::table('permissions')->updateOrInsert(['slug' => $permission['slug']], $permission + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', [
            'production.output.view', 'production.output.create', 'production.output.confirm', 'production.output.reject',
        ])->delete();
    }
};