<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model { protected $guarded=[]; protected $casts=['ordered_at'=>'date','requested_delivery_at'=>'date','production_due_at'=>'date']; public function customer(){return $this->belongsTo(Customer::class);} public function items(){return $this->hasMany(OrderItem::class);} }