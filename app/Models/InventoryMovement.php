<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable=['company_id','goods_id','location_id','quantity','movement_type','reference_type','reference_id','performed_by','transfer_reference','occurred_at','metadata'];
    protected function casts(): array { return ['quantity'=>'decimal:4','occurred_at'=>'datetime','metadata'=>'array']; }
    public function good(){return $this->belongsTo(Good::class,'goods_id');}
    public function location(){return $this->belongsTo(Location::class);}
    public function performer(){return $this->belongsTo(User::class,'performed_by');}
}
