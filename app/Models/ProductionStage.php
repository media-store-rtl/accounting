<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductionStage extends Model { protected $guarded=[]; protected $casts=['attributes'=>'array']; public function route(){return $this->belongsTo(ProductionRoute::class,'production_route_id');} public function operations(){return $this->hasMany(ProductionOperation::class);} }