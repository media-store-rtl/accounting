<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalYear extends Model
{
    protected $fillable = [
        'company_id', 'subscription_entitlement_id', 'name', 'code',
        'starts_at', 'ends_at', 'is_closed',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'is_closed' => 'boolean'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }

    public function subscriptionEntitlement(): BelongsTo
    {
        return $this->belongsTo(SubscriptionEntitlement::class);
    }
}
