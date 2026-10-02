<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['external_company_id', 'name', 'code', 'is_active', 'settings'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'settings' => 'array'];
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role_id', 'is_active'])
            ->withTimestamps();
    }

    public function fiscalYears()
    {
        return $this->hasMany(FiscalYear::class);
    }
}
