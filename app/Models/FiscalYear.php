<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalYear extends Model
{
    protected $fillable = ['company_id', 'name', 'code', 'starts_at', 'ends_at', 'is_closed'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'is_closed' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
