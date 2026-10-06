<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['company_id','code','name','type','is_active','attributes'];

    protected function casts(): array
    {
        return ['attributes' => 'array', 'is_active' => 'boolean'];
    }

    public function company() { return $this->belongsTo(Company::class); }
    public function productionSection() { return $this->hasOne(ProductionSection::class); }
}
