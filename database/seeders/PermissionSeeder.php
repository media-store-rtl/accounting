<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['company.view', 'مشاهده اطلاعات مجموعه', 'company'],
            ['company.update', 'ویرایش اطلاعات مجموعه', 'company'],
            ['fiscal_year.view', 'مشاهده سال‌های مالی', 'fiscal_year'],
            ['fiscal_year.create', 'تعریف سال مالی', 'fiscal_year'],
            ['fiscal_year.update', 'ویرایش سال مالی', 'fiscal_year'],
            ['fiscal_year.close', 'بستن سال مالی', 'fiscal_year'],

            ['user.view', 'مشاهده کاربران', 'users'], ['user.create', 'ایجاد کاربر', 'users'],
            ['user.update', 'ویرایش کاربر', 'users'], ['user.activate', 'فعال‌سازی کاربر', 'users'],
            ['user.deactivate', 'غیرفعال‌سازی کاربر', 'users'], ['user.access.manage', 'مدیریت دسترسی کاربر', 'users'],

            ['personnel.view', 'مشاهده پرسنل', 'personnel'], ['personnel.create', 'ایجاد پرسنل', 'personnel'],
            ['personnel.update', 'ویرایش پرسنل', 'personnel'], ['personnel.deactivate', 'غیرفعال‌سازی پرسنل', 'personnel'],

            ['role.view', 'مشاهده نقش‌ها', 'access'], ['role.create', 'ایجاد نقش', 'access'],
            ['role.update', 'ویرایش نقش', 'access'], ['role.delete', 'حذف نقش', 'access'],

            ['customer.view', 'مشاهده مشتریان', 'sales'], ['customer.create', 'ایجاد مشتری', 'sales'],
            ['customer.update', 'ویرایش مشتری', 'sales'], ['customer.deactivate', 'غیرفعال‌سازی مشتری', 'sales'],
            ['order.view', 'مشاهده سفارش فروش', 'sales'], ['order.create', 'ایجاد سفارش فروش', 'sales'],
            ['order.refresh', 'به‌روزرسانی وضعیت تأمین سفارش', 'sales'],
            ['production.supervise', 'ثبت موعد قابل تحویل تولید', 'production'],
            ['delivery_request.view', 'مشاهده درخواست‌های تحویل', 'sales'],
            ['delivery_request.create', 'ثبت درخواست تحویل', 'sales'],
            ['delivery_request.issue', 'خروج کالا برای تحویل', 'warehouse'],
            ['delivery_request.handover', 'ثبت تحویل به مشتری', 'sales'],
            ['warehouse.delivery.manage', 'مدیریت درخواست تحویل', 'warehouse'],

            ['supply_request.create', 'ایجاد درخواست تأمین', 'supply'],
            ['supply_request.view', 'مشاهده درخواست تأمین', 'supply'],
            ['supply_request.handover.create', 'ثبت تحویل مواد به تولید', 'warehouse'],
            ['supply_request.handover.view', 'مشاهده تحویل مواد به تولید', 'warehouse'],
            ['supply.manage', 'مدیریت کسری و تأمین', 'supply'],

            ['purchase.create', 'ثبت خرید', 'purchasing'], ['purchase.view', 'مشاهده خرید', 'purchasing'],
            ['purchase.receipt.create', 'ثبت رسید خرید', 'warehouse'],
            ['purchase.receipt.approve', 'تأیید رسید خرید', 'warehouse'],
            ['finance.purchase.receive', 'دریافت اعلان ارزش خرید', 'finance'],

            ['production.labor.create', 'ثبت کارکرد تولید', 'production'],
            ['production.labor.review', 'تأیید یا رد کارکرد تولید', 'production'],
            ['production.labor.rate.manage', 'مدیریت نرخ دستمزد', 'production'],

            ['backup.view', 'مشاهده پشتیبان‌ها', 'backup'], ['backup.create', 'ایجاد پشتیبان', 'backup'],
            ['backup.upload', 'بارگذاری پشتیبان', 'backup'], ['backup.restore', 'بازیابی پشتیبان', 'backup'],
            ['import.excel', 'ورود اطلاعات از Excel', 'import'],

            ['costing.report.view', 'مشاهده گزارش بهای تمام‌شده', 'costing'],
        ];

        foreach ($permissions as [$slug, $name, $module]) {
            Permission::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'module' => $module]
            );
        }
    }
}
