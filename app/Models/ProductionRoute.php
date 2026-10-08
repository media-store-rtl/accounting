<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductionRoute extends Model { protected $guarded=[]; protected $casts=['attributes'=>'array']; public function goods(){return $this->belongsTo(Goods::class);} public function stages(){return $this->hasMany(ProductionStage::class);} }