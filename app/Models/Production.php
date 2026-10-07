<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'planned_quantity' => 'decimal:4',
            'produced_quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4',
            'planned_start_at' => 'datetime',
            'planned_end_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function outputs()
    {
        return $this->hasMany(ProductionOutput::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'production_order');
    }
}