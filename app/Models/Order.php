<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    public function productions()
    {
        return $this->belongsToMany(Production::class, 'production_order');
    }

    public function outputs()
    {
        return $this->hasMany(ProductionOutput::class);
    }
}