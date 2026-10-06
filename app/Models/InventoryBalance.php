<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryBalance extends Model
{
    protected $table='inventory';
    protected $fillable=['company_id','location_id','goods_id','quantity','reorder_point'];
    protected function casts(): array { return ['quantity'=>'decimal:4','reorder_point'=>'decimal:4']; }
    public function good(){return $this->belongsTo(Good::class,'goods_id');}
    public function location(){return $this->belongsTo(Location::class);}
}
