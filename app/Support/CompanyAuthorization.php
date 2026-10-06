<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class CompanyAuthorization
{
    public static function authorize(Request $request, string $permission): int
    {
        $companyId=(int)$request->session()->get('company_id');
        abort_unless($companyId>0,403,'شرکت فعال انتخاب نشده است.');
        $member=DB::table('company_user')->where('company_id',$companyId)->where('user_id',$request->user()->id)->where('is_active',true)->exists();
        abort_unless($member,403,'کاربر به این شرکت دسترسی ندارد.');
        $allowed=DB::table('company_user as cu')->join('role_permissions as rp','rp.role_id','=','cu.role_id')->join('permissions as p','p.id','=','rp.permission_id')
            ->where('cu.company_id',$companyId)->where('cu.user_id',$request->user()->id)->where('cu.is_active',true)->where('p.slug',$permission)->exists();
        abort_unless($allowed,403,'مجوز انجام این عملیات را ندارید.');
        return $companyId;
    }
}