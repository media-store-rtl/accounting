<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Personnel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->currentCompany();
        abort_unless($company, 409);
        $users = $company->users()->with(['personnel', 'roles'])->paginate(20);
        $roles = $company->roles()->with('permissions')->orderBy('name')->get();
        return view('users.index', compact('users', 'roles', 'company'));
    }

    public function create(Request $request): View
    {
        $company = $request->user()->currentCompany();
        abort_unless($company, 409);
        $roles = $company->roles()->with('permissions')->orderBy('name')->get();
        return view('users.form', compact('company', 'roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        $company = $actor->currentCompany();
        abort_unless($company, 409);

        $data = $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('personnel', 'code')->where(fn ($query) => $query->where('account_id', $actor->account_id)),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => [
                'required', 'string', 'max:100', 'alpha_dash',
                Rule::unique('users', 'username')->where(fn ($query) => $query->where('account_id', $actor->account_id)),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['nullable', 'integer'],
        ]);

        $this->assertUserCapacity($company);

        $role = ! empty($data['role_id'])
            ? $company->roles()->whereKey($data['role_id'])->firstOrFail()
            : null;

        DB::transaction(function () use ($data, $actor, $company, $role) {
            $user = User::create([
                'account_id' => $actor->account_id,
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => true,
            ]);

            Personnel::create([
                'account_id' => $actor->account_id,
                'user_id' => $user->id,
                'code' => $data['code'],
                'name' => $user->name,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'national_id' => $data['national_id'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'phone' => $data['phone'] ?? null,
                'mobile' => $data['mobile'] ?? null,
                'email' => $data['email'],
                'is_active' => true,
            ]);

            $company->users()->attach($user->id, [
                'role_id' => $role?->id,
                'is_active' => true,
            ]);
        });

        return redirect()->route('users.index')->with('success', 'کاربر و پرسنل ایجاد شد.');
    }

    public function edit(Request $request, User $user): View
    {
        $company = $this->assertUserInCompany($request, $user);
        $roles = $company->roles()->with('permissions')->orderBy('name')->get();
        return view('users.form', compact('company', 'roles', 'user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $company = $this->assertUserInCompany($request, $user);
        abort_if($user->isAccountOwner(), 403, 'مالک حساب قابل ویرایش از این مسیر نیست.');

        $data = $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('personnel', 'code')
                    ->where(fn ($query) => $query->where('account_id', $user->account_id))
                    ->ignore($user->personnel?->id),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'username' => [
                'required', 'string', 'max:100', 'alpha_dash',
                Rule::unique('users', 'username')
                    ->where(fn ($query) => $query->where('account_id', $user->account_id))
                    ->ignore($user),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role_id' => ['nullable', 'integer'],
        ]);

        $role = ! empty($data['role_id']) ? $company->roles()->whereKey($data['role_id'])->firstOrFail() : null;

        DB::transaction(function () use ($data, $user, $company, $role) {
            $user->update([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'username' => $data['username'],
                'email' => $data['email'],
            ]);
            if (! empty($data['password'])) {
                $user->update(['password' => $data['password']]);
            }

            $user->personnel()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'account_id' => $user->account_id,
                    'code' => $data['code'],
                    'name' => $user->name,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'national_id' => $data['national_id'] ?? null,
                    'job_title' => $data['job_title'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'mobile' => $data['mobile'] ?? null,
                    'email' => $data['email'],
                ]
            );

            $company->users()->updateExistingPivot($user->id, ['role_id' => $role?->id]);
        });

        return redirect()->route('users.index')->with('success', 'کاربر و پرسنل ویرایش شد.');
    }

    public function access(Request $request, User $user): View
    {
        $company = $this->assertUserInCompany($request, $user);
        $roles = $company->roles()->with('permissions')->orderBy('name')->get();
        return view('users.access', compact('user', 'company', 'roles'));
    }

    public function updateAccess(Request $request, User $user): RedirectResponse
    {
        $company = $this->assertUserInCompany($request, $user);
        abort_if($user->isAccountOwner(), 403, 'دسترسی مالک حساب قابل تغییر نیست.');

        $data = $request->validate(['role_id' => ['nullable', 'integer']]);
        $role = ! empty($data['role_id']) ? $company->roles()->whereKey($data['role_id'])->firstOrFail() : null;
        $company->users()->updateExistingPivot($user->id, ['role_id' => $role?->id]);

        return redirect()->route('users.index')->with('success', 'دسترسی کاربر به‌روزرسانی شد.');
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        $company = $this->assertUserInCompany($request, $user);
        abort_if($user->isAccountOwner(), 403);
        $this->assertUserCapacity($company);

        $user->update(['is_active' => true]);
        $user->personnel()->update(['is_active' => true]);
        $company->users()->updateExistingPivot($user->id, ['is_active' => true]);

        return back()->with('success', 'کاربر فعال شد.');
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $company = $this->assertUserInCompany($request, $user);
        abort_if($user->isAccountOwner(), 403);
        abort_if((int) $user->id === (int) $request->user()->id, 422);

        $user->update(['is_active' => false]);
        $user->personnel()->update(['is_active' => false]);
        $company->users()->updateExistingPivot($user->id, ['is_active' => false]);

        return back()->with('success', 'کاربر غیرفعال شد.');
    }

    private function assertUserCapacity(Company $company): void
    {
        $maxUsers = $company->subscriptionEntitlement?->effectiveMaxUsers();
        abort_unless($maxUsers !== null, 409, 'سقف کاربران پلن در اطلاعات اشتراک مشخص نشده است.');

        abort_if(
            $company->users()->wherePivot('is_active', true)->count() >= $maxUsers,
            422,
            'سقف تعداد کاربران پلن تکمیل شده است.'
        );
    }

    private function assertUserInCompany(Request $request, User $user): Company
    {
        $company = $request->user()->currentCompany();
        abort_unless($company && (int) $user->account_id === (int) $request->user()->account_id, 404);
        abort_unless($company->users()->whereKey($user->id)->exists(), 404);
        return $company;
    }
}