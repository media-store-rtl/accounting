<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOutput extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'produced_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function receipt()
    {
        return $this->hasOne(FinishedGoodsReceipt::class);
    }
}