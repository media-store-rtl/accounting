<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionEntitlement extends Model
{
    protected $fillable = [
        'company_id',
        'external_subscription_id',
        'status',
        'starts_at',
        'expires_at',
        'last_verified_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_verified_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
