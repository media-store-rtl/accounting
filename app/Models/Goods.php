<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Goods extends Model { protected $table='goods'; protected $guarded=[]; protected $casts=['purchasable'=>'boolean','producible'=>'boolean','sellable'=>'boolean','is_active'=>'boolean','attributes'=>'array']; }