<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsUnit extends Model
{
    protected $table = 'goods_units';
    protected $fillable = ['goods_id','unit_id','conversion_factor','is_base'];
    protected function casts(): array { return ['conversion_factor' => 'decimal:6','is_base' => 'boolean']; }
    public function good() { return $this->belongsTo(Good::class, 'goods_id'); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
