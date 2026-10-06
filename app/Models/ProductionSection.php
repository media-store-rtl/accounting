<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionSection extends Model
{
    protected $fillable = ['location_id','supervisor_personnel_id'];

    public function location() { return $this->belongsTo(Location::class); }
    public function supervisor() { return $this->belongsTo(Personnel::class, 'supervisor_personnel_id'); }
    public function personnel() { return $this->belongsToMany(Personnel::class, 'personnel_production_sections'); }
}
