<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarnamLead extends Model
{
    protected $fillable = [
        'name', 'phone', 'business_name', 'business_type', 'service_interest',
        'message', 'status', 'source', 'source_detail', 'consent_at', 'internal_notes',
    ];

    protected function casts(): array
    {
        return ['consent_at' => 'datetime'];
    }
}
