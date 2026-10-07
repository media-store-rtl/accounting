<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'account_id','name','trade_name','type','national_id','registration_number',
        'economic_code','phone','mobile','email','website','province','city',
        'address','postal_code','logo_path','code','is_active','settings',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'settings' => 'array'];
    }

    public function account() { return $this->belongsTo(Account::class); }
    public function users() { return $this->belongsToMany(User::class)->withPivot(['role_id', 'is_active'])->withTimestamps(); }
    public function roles() { return $this->hasMany(Role::class); }
    public function fiscalYears() { return $this->hasMany(FiscalYear::class); }
    public function subscriptionEntitlement() { return $this->hasOne(SubscriptionEntitlement::class); }
}
