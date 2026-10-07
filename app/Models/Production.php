<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    protected $fillable = [
        'company_id',
        'fiscal_year_id',
        'order_id',
        'order_item_id',
        'goods_id',
        'production_route_id',
        'number',
        'planned_quantity',
        'produced_quantity',
        'rejected_quantity',
        'status',
        'planned_start_at',
        'planned_end_at',
        'started_at',
        'completed_at',
        'notes',
    ];

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

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function goods()
    {
        return $this->belongsTo(Goods::class);
    }

    public function productionRoute()
    {
        return $this->belongsTo(ProductionRoute::class);
    }
}