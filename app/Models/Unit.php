<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = ['company_id','name','code','symbol','unit_type','is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
}
