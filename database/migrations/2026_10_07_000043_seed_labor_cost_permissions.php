<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $permissions = [
            ['production.labor.view', 'مشاهده کارکرد تولید'],
            ['production.labor.create', 'ثبت کارکرد تولید'],
            ['production.labor.review', 'تأیید/رد کارکرد تولید'],
            ['production.labor.rate.manage', 'مدیریت نرخ دستمزد تولید'],
        ];
        foreach ($permissions as [$slug, $name]) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $slug],
                ['name' => $name, 'module' => 'production', 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', [
            'production.labor.view','production.labor.create','production.labor.review','production.labor.rate.manage',
        ])->delete();
    }
};