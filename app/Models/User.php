<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        return $this->companies()->wherePivot('is_active', true)->first();
    }

    public function isAccountOwner(): bool
    {
        return $this->account !== null && (int) $this->account->owner_user_id === (int) $this->id;
    }

    public function hasPermission(string $permission, ?Company $company = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->isAccountOwner()) {
            return true;
        }

        $company ??= $this->currentCompany();

        if (! $company || (int) $company->account_id !== (int) $this->account_id) {
            return false;
        }

        return $this->roles()
            ->where('roles.company_id', $company->id)
            ->wherePivot('company_id', $company->id)
            ->wherePivot('is_active', true)
            ->whereHas('permissions', fn ($q) => $q->where('permissions.slug', $permission))
            ->exists();
    }
}