<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Good extends Model
{
    protected $table='goods';
    protected $fillable=['company_id','category_id','code','name','item_type','product_type','purchasable','producible','sellable','description','is_active','attributes'];
    protected function casts(): array { return ['purchasable'=>'boolean','producible'=>'boolean','sellable'=>'boolean','is_active'=>'boolean','attributes'=>'array']; }
    public function company(){return $this->belongsTo(Company::class);}
    public function category(){return $this->belongsTo(GoodsCategory::class,'category_id');}
    public function suppliers(){return $this->belongsToMany(Supplier::class,'supplier_goods','goods_id','supplier_id')->withTimestamps();}
    public function units(){return $this->hasMany(GoodsUnit::class);}
    public function inventory(){return $this->hasMany(InventoryBalance::class,'goods_id');}
}
