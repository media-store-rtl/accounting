<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class AuditTrail extends Model { protected $guarded=[]; protected $casts=['before'=>'array','after'=>'array']; public function user(){return $this->belongsTo(User::class);} public function company(){return $this->belongsTo(Company::class);} public function auditable(){return $this->morphTo();} }