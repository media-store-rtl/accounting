<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Production extends Model { protected $guarded=[]; protected $casts=['planned_start_at'=>'datetime','planned_end_at'=>'datetime','started_at'=>'datetime','completed_at'=>'datetime','planned_quantity'=>'decimal:4','produced_quantity'=>'decimal:4','rejected_quantity'=>'decimal:4']; public function route(){return $this->belongsTo(ProductionRoute::class,'production_route_id');} public function goods(){return $this->belongsTo(Goods::class);} public function outputs(){return $this->hasMany(ProductionOutput::class);} }