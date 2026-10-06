<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    protected $fillable = [
        'account_id', 'name', 'code', 'commercial_name', 'entity_type',
        'national_id', 'registration_number', 'economic_code', 'phone',
        'mobile', 'email', 'website', 'province', 'city', 'address',
        'postal_code', 'logo_path', 'is_active', 'settings',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'settings' => 'array'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role_id', 'is_active'])
            ->withTimestamps();
    }

    public function fiscalYears(): HasMany
    {
        return $this->hasMany(FiscalYear::class);
    }

    public function subscriptionEntitlement(): HasOne
    {
        return $this->hasOne(SubscriptionEntitlement::class);
    }
}
