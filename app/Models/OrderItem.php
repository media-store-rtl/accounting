<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OrderItem extends Model { protected $guarded=[]; public function order(){return $this->belongsTo(Order::class);} public function goods(){return $this->belongsTo(Goods::class);} }