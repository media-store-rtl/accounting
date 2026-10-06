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

            ['supplier.view', 'مشاهده تأمین‌کنندگان', 'supplier'],
            ['supplier.create', 'ایجاد تأمین‌کننده', 'supplier'],
            ['supplier.update', 'ویرایش تأمین‌کننده', 'supplier'],
            ['supplier.activate', 'فعال‌سازی تأمین‌کننده', 'supplier'],
            ['supplier.deactivate', 'غیرفعال‌سازی تأمین‌کننده', 'supplier'],
            ['warehouse.view', 'مشاهده انبارها', 'warehouse'],
            ['warehouse.create', 'ایجاد انبار', 'warehouse'],
            ['warehouse.update', 'ویرایش انبار', 'warehouse'],
            ['warehouse.activate', 'فعال‌سازی انبار', 'warehouse'],
            ['warehouse.deactivate', 'غیرفعال‌سازی انبار', 'warehouse'],
            ['production_section.view', 'مشاهده قسمت‌های تولید', 'production'],
            ['production_section.create', 'ایجاد قسمت تولید', 'production'],
            ['production_section.update', 'ویرایش قسمت تولید', 'production'],
            ['production_section.activate', 'فعال‌سازی قسمت تولید', 'production'],
            ['production_section.deactivate', 'غیرفعال‌سازی قسمت تولید', 'production'],
            ['goods.view', 'مشاهده کالاها', 'goods'],
            ['goods.create', 'ایجاد کالا', 'goods'],
            ['goods.update', 'ویرایش کالا', 'goods'],
            ['goods.activate', 'فعال‌سازی کالا', 'goods'],
            ['goods.deactivate', 'غیرفعال‌سازی کالا', 'goods'],
            ['goods.operation.view', 'مشاهده عملیات کالا', 'goods'],
            ['goods.operation.create', 'ثبت عملیات کالا', 'goods'],
            ['goods.operation.adjust', 'اصلاح موجودی کالا', 'goods'],
        ];

        foreach ($permissions as [$slug, $name, $module]) {
            Permission::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'module' => $module,
            ]);
        }
    }
}
