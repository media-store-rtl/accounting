<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        foreach ([
            ['name' => 'View Backups', 'slug' => 'backup.view', 'module' => 'backup'],
            ['name' => 'Create Backup', 'slug' => 'backup.create', 'module' => 'backup'],
            ['name' => 'Upload Backup', 'slug' => 'backup.upload', 'module' => 'backup'],
            ['name' => 'Restore Backup', 'slug' => 'backup.restore', 'module' => 'backup'],
            ['name' => 'Import Excel', 'slug' => 'import.excel', 'module' => 'import'],
        ] as $permission) {
            DB::table('permissions')->updateOrInsert(['slug' => $permission['slug']], $permission + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', ['backup.view', 'backup.create', 'backup.upload', 'backup.restore', 'import.excel'])->delete();
    }
};
