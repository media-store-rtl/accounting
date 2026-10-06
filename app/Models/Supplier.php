<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable=['company_id','name','code','national_id','phone','email','address','settings','is_active'];
    protected function casts(): array { return ['settings'=>'array','is_active'=>'boolean']; }
    public function company(){return $this->belongsTo(Company::class);}
    public function goods(){return $this->belongsToMany(Good::class,'supplier_goods','supplier_id','goods_id')->withTimestamps();}
}
