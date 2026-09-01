<?php
namespace App\Models;use Astrotomic\Translatable\Contracts\Translatable as Contract;use Astrotomic\Translatable\Translatable;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;
class PickupPoint extends Model implements Contract{use Translatable,SoftDeletes;public array $translatedAttributes=['name','instructions'];protected $fillable=['address','map_url','is_active','sort_order'];protected $casts=['is_active'=>'boolean'];}
