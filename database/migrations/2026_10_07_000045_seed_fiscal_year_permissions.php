<?php
use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
 public function up(): void { foreach([['fiscal_year.view','مشاهده سال‌های مالی'],['fiscal_year.create','تعریف سال مالی'],['fiscal_year.update','ویرایش سال مالی'],['fiscal_year.close','بستن سال مالی']] as [$slug,$name]) Permission::updateOrCreate(['slug'=>$slug],['name'=>$name,'module'=>'fiscal_year']); }
 public function down(): void { Permission::whereIn('slug',['fiscal_year.view','fiscal_year.create','fiscal_year.update','fiscal_year.close'])->delete(); }
};