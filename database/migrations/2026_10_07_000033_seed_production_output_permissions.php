<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $permissions=[
            ['production.output.view','مشاهده خروجی تولید'],
            ['production.output.create','ثبت خروجی تولید'],
            ['production.output.confirm','تأیید دریافت کالای ساخته‌شده'],
            ['production.output.reject','رد خروجی تولید'],
        ];
        foreach($permissions as [$slug,$name]) DB::table('permissions')->updateOrInsert(['slug'=>$slug],['name'=>$name,'module'=>'production','updated_at'=>now(),'created_at'=>now()]);
    }
    public function down(): void
    {
        DB::table('permissions')->whereIn('slug',['production.output.view','production.output.create','production.output.confirm','production.output.reject'])->delete();
    }
};