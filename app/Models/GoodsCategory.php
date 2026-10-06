<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsCategory extends Model
{
    protected $table = 'goods_categories';
    protected $fillable = ['company_id','parent_id','name','code','is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
}
