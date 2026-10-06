<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'account_id', 'name', 'username', 'email', 'password',
        'web2022_user_id', 'web2022_subscription_id', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['role_id', 'is_active'])
            ->withTimestamps();
    }

    public function personnel()
    {
        return $this->hasOne(Personnel::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'company_user')
            ->withPivot(['company_id', 'role_id', 'is_active'])
            ->withTimestamps();
    }

    public function currentCompany(): ?Company
    {
        $companyId = (int) session('company_id');

        $query = $this->companies()
            ->where('companies.is_active', true)
            ->wherePivot('is_active', true);

        if ($companyId > 0) {
            $query->where('companies.id', $companyId);
        }

        return $query->first() ?? $this->companies()
            ->where('companies.is_active', true)
            ->wherePivot('is_active', true)
            ->first();
    }

    public function isAccountOwner(): bool
    {
        return $this->account !== null && (int) $this->account->owner_user_id === (int) $this->id;
    }

    public function hasCompanyPermission(int $companyId, string $permission): bool
    {
        if (! $this->is_active || ! $this->companies()->whereKey($companyId)->wherePivot('is_active', true)->exists()) {
            return false;
        }

        if ($this->isAccountOwner()) {
            return true;
        }

        return DB::table('company_user')
            ->join('roles', 'roles.id', '=', 'company_user.role_id')
            ->join('role_permissions', 'role_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('company_user.company_id', $companyId)
            ->where('company_user.user_id', $this->getKey())
            ->where('company_user.is_active', true)
            ->where('permissions.slug', $permission)
            ->exists();
    }

    public function hasPermission(string $permission, ?Company $company = null): bool
    {
        $company ??= $this->currentCompany();
        return $company ? $this->hasCompanyPermission((int) $company->id, $permission) : false;
    }
}