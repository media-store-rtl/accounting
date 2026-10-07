<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class ProductionRoute extends Model
{
    protected $guarded = [];

    public function goods()
    {
        return $this->belongsTo(Goods::class);
    }

    public function productions()
    {
        return $this->hasMany(Production::class);
    }
}