<?php

namespace App\Http\Controllers;

use App\Support\CompanyAuthorization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizationalDepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = CompanyAuthorization::authorize($request, 'user.update');
        $departments = DB::table('organizational_departments')->where('company_id', $companyId)
            ->orderBy('is_active', 'desc')->orderBy('name')->get();
        $membersByDepartment = DB::table('organizational_department_user')->where('company_id', $companyId)
            ->get()->groupBy('department_id')->map(fn ($rows) => $rows->pluck('user_id')->map(fn ($id) => (int) $id)->all());
        $users = DB::table('company_user as cu')->join('users as u', 'u.id', '=', 'cu.user_id')
            ->where('cu.company_id', $companyId)->where('cu.is_active', true)->where('u.is_active', true)
            ->select('u.id', 'u.name', 'u.email')->orderBy('u.name')->get();

        return view('settings.departments.index', compact('departments', 'membersByDepartment', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = CompanyAuthorization::authorize($request, 'user.update');
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('organizational_departments', 'code')->where('company_id', $companyId)],
            'name' => ['required', 'string', 'max:150', Rule::unique('organizational_departments', 'name')->where('company_id', $companyId)],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        DB::table('organizational_departments')->insert([
            'company_id' => $companyId, 'code' => trim($data['code']), 'name' => trim($data['name']),
            'description' => $data['description'] ?? null, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return back()->with('success', 'واحد سازمانی ایجاد شد.');
    }

    public function update(Request $request, int $department): RedirectResponse
    {
        $companyId = CompanyAuthorization::authorize($request, 'user.update');
        $row = DB::table('organizational_departments')->where('id', $department)->where('company_id', $companyId)->first();
        abort_unless($row, 404);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('organizational_departments', 'code')->where('company_id', $companyId)->ignore($department)],
            'name' => ['required', 'string', 'max:150', Rule::unique('organizational_departments', 'name')->where('company_id', $companyId)->ignore($department)],
            'description' => ['nullable', 'string', 'max:2000'], 'is_active' => ['nullable', 'boolean'],
        ]);
        DB::table('organizational_departments')->where('id', $department)->where('company_id', $companyId)->update([
            'code' => trim($data['code']), 'name' => trim($data['name']), 'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false), 'updated_at' => now(),
        ]);
        return back()->with('success', 'واحد سازمانی به‌روزرسانی شد.');
    }

    public function syncMembers(Request $request, int $department): RedirectResponse
    {
        $companyId = CompanyAuthorization::authorize($request, 'user.update');
        $row = DB::table('organizational_departments')->where('id', $department)->where('company_id', $companyId)->first();
        abort_unless($row, 404);
        abort_unless($row->is_active, 422, 'برای واحد غیرفعال نمی‌توان انتساب جدید ثبت کرد.');
        $data = $request->validate(['users' => ['nullable', 'array'], 'users.*' => ['integer']]);
        $userIds = collect($data['users'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $validIds = DB::table('company_user as cu')->join('users as u', 'u.id', '=', 'cu.user_id')
            ->where('cu.company_id', $companyId)->where('cu.is_active', true)->where('u.is_active', true)
            ->whereIn('u.id', $userIds)->pluck('u.id')->map(fn ($id) => (int) $id);
        abort_unless($validIds->count() === $userIds->count(), 422, 'یکی از کاربران انتخاب‌شده عضو فعال این شرکت نیست.');

        DB::transaction(function () use ($companyId, $department, $userIds) {
            DB::table('organizational_department_user')->where('company_id', $companyId)->where('department_id', $department)->delete();
            foreach ($userIds as $userId) {
                DB::table('organizational_department_user')->insert([
                    'company_id' => $companyId, 'department_id' => $department, 'user_id' => $userId,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
        return back()->with('success', 'اعضای واحد ذخیره شدند.');
    }

    public function deactivate(Request $request, int $department): RedirectResponse
    {
        $companyId = CompanyAuthorization::authorize($request, 'user.update');
        $updated = DB::table('organizational_departments')->where('id', $department)->where('company_id', $companyId)
            ->update(['is_active' => false, 'updated_at' => now()]);
        abort_unless($updated, 404);
        return back()->with('success', 'واحد غیرفعال شد؛ سوابق و انتساب‌های قبلی حفظ شدند.');
    }
}