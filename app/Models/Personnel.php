<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Personnel extends Model
{
    protected $table = 'personnel';

    protected $fillable = [
        'account_id', 'user_id', 'code', 'name', 'first_name', 'last_name',
        'national_id', 'job_title', 'phone', 'mobile', 'email',
        'employment_type', 'is_active', 'attributes',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'attributes' => 'array'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}