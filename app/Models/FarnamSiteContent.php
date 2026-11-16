<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FarnamSiteContent extends Model
{
    protected $fillable = ['key', 'content'];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }
}
