<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'external_subscription_id',
        'plan_id',
        'status',
        'max_users',
        'starts_at',
        'expires_at',
        'last_synced_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'plan_id' => 'integer',
            'max_users' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && $this->starts_at?->lte(now()) === true
            && $this->expires_at?->gt(now()) === true;
    }
}
