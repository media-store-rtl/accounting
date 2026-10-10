<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    private const MODULE_LABELS = [
        'company' => 'اطلاعات مجموعه',
        'fiscal_year' => 'سال‌های مالی',
        'users' => 'کاربران و دسترسی‌ها',
        'personnel' => 'پرسنل',
        'access' => 'مدیریت نقش‌ها',
        'sales' => 'فروش و سفارش‌ها',
        'production' => 'تولید',
        'warehouse' => 'انبار و تحویل',
        'supply' => 'تأمین',
        'purchasing' => 'خرید',
        'finance' => 'مالی',
        'notifications' => 'اعلان‌ها',
        'backup' => 'پشتیبان‌گیری',
        'import' => 'ورود اطلاعات',
        'costing' => 'گزارش بهای تمام‌شده',
        'goods' => 'کالاها و واحدها',
        'suppliers' => 'تأمین‌کنندگان',
    ];

    public function index(Request $request): View
    {
        $company = $request->user()->currentCompany();
        abort_unless($company, 409);

        $roles = $company->roles()
            ->with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('roles.index', compact('roles'));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->currentCompany(), 409);

        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');
        $moduleLabels = self::MODULE_LABELS;

        return view('roles.create', compact('permissions', 'moduleLabels'));
    }

    public function show(Request $request, Role $role): View
    {
        $company = $request->user()->currentCompany();
        abort_unless($company && (int) $role->company_id === (int) $company->id, 404);

        $role->load('permissions');
        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');
        $moduleLabels = self::MODULE_LABELS;

        return view('roles.show', compact('role', 'permissions', 'moduleLabels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->currentCompany();
        abort_unless($company, 409);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = $company->roles()->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'is_system' => false,
        ]);
        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('roles.show', $role)->with('success', 'نقش ایجاد شد.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $company = $request->user()->currentCompany();
        abort_unless($company && (int) $role->company_id === (int) $company->id, 404);
        abort_if($role->is_system, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
        ]);
        $role->permissions()->sync($data['permissions'] ?? []);

        return redirect()->route('roles.show', $role)->with('success', 'تغییرات نقش ذخیره شد.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $company = $request->user()->currentCompany();
        abort_unless($company && (int) $role->company_id === (int) $company->id, 404);
        abort_if($role->is_system, 403);
        abort_if($role->users()->wherePivot('company_id', $company->id)->exists(), 422, 'این نقش به کاربر اختصاص دارد و قابل حذف نیست.');

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'نقش حذف شد.');
    }
}
