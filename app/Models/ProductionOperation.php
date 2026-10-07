<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductionOperation extends Model { protected $guarded=[]; protected $casts=['attributes'=>'array']; public function stage(){return $this->belongsTo(ProductionStage::class,'production_stage_id');} }