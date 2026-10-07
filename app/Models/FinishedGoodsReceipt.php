<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedGoodsReceipt extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function productionOutput()
    {
        return $this->belongsTo(ProductionOutput::class);
    }
}