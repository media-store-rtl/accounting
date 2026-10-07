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
            ['goods_category.view','مشاهده دسته‌بندی کالا','goods'],['goods_category.create','ایجاد دسته‌بندی کالا','goods'],['goods_category.update','ویرایش دسته‌بندی کالا','goods'],['goods_category.deactivate','غیرفعال‌سازی دسته‌بندی کالا','goods'],['unit.view','مشاهده واحدها','goods'],['unit.create','ایجاد واحد','goods'],['unit.update','ویرایش واحد','goods'],['unit.deactivate','غیرفعال‌سازی واحد','goods'],['production_section.view','مشاهده قسمت‌های تولید','production'],['production_section.create','ایجاد قسمت تولید','production'],['production_section.update','ویرایش قسمت تولید','production'],['production_section.deactivate','غیرفعال‌سازی قسمت تولید','production'],
            ['supplier.view','مشاهده تأمین‌کنندگان','suppliers'],['supplier.create','ایجاد تأمین‌کننده','suppliers'],['supplier.update','ویرایش تأمین‌کننده','suppliers'],['supplier.deactivate','غیرفعال‌سازی تأمین‌کننده','suppliers'],
            ['goods.view','مشاهده کالاها','goods'],['goods.create','ایجاد کالا','goods'],['goods.update','ویرایش کالا','goods'],['goods.deactivate','غیرفعال‌سازی کالا','goods'],
            ['location.view','مشاهده انبارها و محل‌ها','warehouse'],['location.create','ایجاد انبار یا محل','warehouse'],['location.update','ویرایش انبار یا محل','warehouse'],['location.deactivate','غیرفعال‌سازی انبار یا محل','warehouse'],
            ['production_route.view','مشاهده مسیرهای تولید','production'],['production_route.create','ایجاد مسیر تولید','production'],['production_route.update','ویرایش مسیر تولید','production'],['production_route.deactivate','غیرفعال‌سازی مسیر تولید','production'],
            ['production_stage.view','مشاهده مراحل تولید','production'],['production_stage.create','ایجاد مرحله تولید','production'],['production_stage.update','ویرایش مرحله تولید','production'],['production_stage.deactivate','غیرفعال‌سازی مرحله تولید','production'],
            ['production_operation.view','مشاهده عملیات تولید','production'],['production_operation.create','ایجاد عملیات تولید','production'],['production_operation.update','ویرایش عملیات تولید','production'],['production_operation.deactivate','غیرفعال‌سازی عملیات تولید','production'],
            ['production.view','مشاهده تولید','production'],['production.create','ایجاد دستور تولید','production'],['production.update','ویرایش دستور تولید','production'],['production.deactivate','غیرفعال‌سازی دستور تولید','production'],
            ['production.output.view','مشاهده خروجی تولید','production'],['production.output.create','ثبت خروجی تولید','production'],['production.output.confirm','تأیید/رد خروجی تولید','production'],['production.output.receive','دریافت کالای ساخته‌شده','warehouse'],['inventory.view','مشاهده موجودی','warehouse'],
        ];

        foreach ($permissions as [$slug, $name, $module]) {
            Permission::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'module' => $module]
            );
        }
    }
}
