<?php

namespace App\Support;

use Illuminate\Http\Request;

final class CompanyAuthorization
{
    public static function authorize(Request $request, string $permission): int
    {
        $user = $request->user();
        $companyId = (int) $request->session()->get('company_id');

        abort_unless($user && $companyId > 0, 403, 'شرکت فعال انتخاب نشده است.');

        abort_unless(
            $user->hasCompanyPermission($companyId, $permission),
            403,
            'مجوز انجام این عملیات را ندارید.'
        );

        return $companyId;
    }
}
