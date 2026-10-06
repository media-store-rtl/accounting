<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['user.view', 'مشاهده کاربران', 'users'],
            ['user.create', 'ایجاد کاربر', 'users'],
            ['user.update', 'ویرایش کاربر', 'users'],
            ['user.activate', 'فعال‌سازی کاربر', 'users'],
            ['user.deactivate', 'غیرفعال‌سازی کاربر', 'users'],
            ['user.access.manage', 'مدیریت دسترسی کاربر', 'users'],
            ['personnel.view', 'مشاهده پرسنل', 'personnel'],
            ['personnel.create', 'ایجاد پرسنل', 'personnel'],
            ['personnel.update', 'ویرایش پرسنل', 'personnel'],
            ['personnel.deactivate', 'غیرفعال‌سازی پرسنل', 'personnel'],
            ['role.view', 'مشاهده نقش‌ها', 'access'],
            ['role.create', 'ایجاد نقش', 'access'],
            ['role.update', 'ویرایش نقش', 'access'],
            ['role.delete', 'حذف نقش', 'access'],
        ];

        foreach ($permissions as [$slug, $name, $module]) {
            Permission::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'module' => $module,
            ]);
        }
    }
}