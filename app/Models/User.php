<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class)
            ->withPivot(['role_id', 'is_active'])
            ->withTimestamps();
    }

    public function hasCompanyPermission(int $companyId, string $permission): bool
    {
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
}
