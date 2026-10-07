<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $permissions=[
            ['customer.view','مشاهده مشتریان'],['customer.create','ایجاد مشتری'],['customer.update','ویرایش مشتری'],['customer.deactivate','غیرفعال‌سازی مشتری'],
            ['order.view','مشاهده سفارش فروش'],['order.create','ایجاد سفارش فروش'],['order.refresh','به‌روزرسانی وضعیت تأمین سفارش'],
            ['production.supervise','ثبت موعد قابل تحویل تولید'],['delivery_request.view','مشاهده درخواست‌های تحویل'],['delivery_request.create','ثبت درخواست تحویل'],
            ['delivery_request.issue','خروج کالا برای تحویل'],['delivery_request.handover','ثبت تحویل به مشتری'],['warehouse.delivery.manage','مدیریت درخواست تحویل']
        ];
        foreach($permissions as [$slug,$name]) DB::table('permissions')->updateOrInsert(['slug'=>$slug],['name'=>$name,'module'=>'sales','updated_at'=>now(),'created_at'=>now()]);
    }
    public function down(): void
    {
        DB::table('permissions')->where('module','sales')->delete();
    }
};
