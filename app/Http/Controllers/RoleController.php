<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->currentCompany();
        abort_unless($company, 409);
        $roles = $company->roles()->with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('module')->orderBy('name')->get();

        return view('roles.index', compact('roles', 'permissions'));
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

        return back()->with('success', 'نقش ایجاد شد.');
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

        return back()->with('success', 'نقش به‌روزرسانی شد.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $company = $request->user()->currentCompany();
        abort_unless($company && (int) $role->company_id === (int) $company->id, 404);
        abort_if($role->is_system, 403);
        abort_if($role->users()->exists(), 422, 'نقش دارای کاربر است و قابل حذف نیست.');

        $role->delete();
        return back()->with('success', 'نقش حذف شد.');
    }
}
