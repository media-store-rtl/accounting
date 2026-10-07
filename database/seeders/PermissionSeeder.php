<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['user.view', 'مشاهده کاربران', 'users'],['user.create', 'ایجاد کاربر', 'users'],['user.update', 'ویرایش کاربر', 'users'],
            ['user.activate', 'فعال‌سازی کاربر', 'users'],['user.deactivate', 'غیرفعال‌سازی کاربر', 'users'],['user.access.manage', 'مدیریت دسترسی کاربر', 'users'],
            ['personnel.view', 'مشاهده پرسنل', 'personnel'],['personnel.create', 'ایجاد پرسنل', 'personnel'],['personnel.update', 'ویرایش پرسنل', 'personnel'],
            ['personnel.deactivate', 'غیرفعال‌سازی پرسنل', 'personnel'],['role.view', 'مشاهده نقش‌ها', 'access'],['role.create', 'ایجاد نقش', 'access'],
            ['role.update', 'ویرایش نقش', 'access'],['role.delete', 'حذف نقش', 'access'],
            ['customer.view','مشاهده مشتریان','sales'],['customer.create','ایجاد مشتری','sales'],['customer.update','ویرایش مشتری','sales'],
            ['customer.deactivate','غیرفعال‌سازی مشتری','sales'],['order.view','مشاهده سفارش فروش','sales'],['order.create','ایجاد سفارش فروش','sales'],
            ['order.refresh','به‌روزرسانی وضعیت تأمین سفارش','sales'],['production.supervise','ثبت موعد قابل تحویل تولید','sales'],
            ['delivery_request.view','مشاهده درخواست‌های تحویل','sales'],['delivery_request.create','ثبت درخواست تحویل','sales'],
            ['delivery_request.issue','خروج کالا برای تحویل','sales'],['delivery_request.handover','ثبت تحویل به مشتری','sales'],
            ['warehouse.delivery.manage','مدیریت درخواست تحویل','sales'],
        ];
        foreach ($permissions as [$slug, $name, $module]) Permission::updateOrCreate(['slug' => $slug], ['name' => $name, 'module' => $module]);
    }
}
