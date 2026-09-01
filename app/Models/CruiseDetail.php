<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class CruiseDetail extends Model{protected $fillable=['tour_id','company','departure_port','cabin_type','documents'];protected $casts=['documents'=>'array'];public function tour(){return $this->belongsTo(Tour::class);}}
