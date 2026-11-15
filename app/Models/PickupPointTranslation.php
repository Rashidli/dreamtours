<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class PickupPointTranslation extends Model{use \App\Models\Concerns\RecordsActivity;public $timestamps=false;protected $fillable=['name','instructions'];}
