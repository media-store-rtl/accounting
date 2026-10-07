<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionEntitlement extends Model
{
    protected $fillable = [
        'company_id', 'external_subscription_id', 'status',
        'max_users', 'starts_at', 'expires_at', 'last_verified_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'max_users' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_verified_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }

    public function fiscalYears(): HasMany { return $this->hasMany(FiscalYear::class); }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
