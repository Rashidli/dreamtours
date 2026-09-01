<?php
namespace App\Models;
use Astrotomic\Translatable\Contracts\Translatable as Contract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Destination extends Model implements Contract { use Translatable, SoftDeletes; public array $translatedAttributes=['name','slug','description']; protected $fillable=['parent_id','type','image','is_active','sort_order']; protected $casts=['is_active'=>'boolean']; public function parent(){return $this->belongsTo(self::class,'parent_id');} public function children(){return $this->hasMany(self::class,'parent_id');} public function tours(){return $this->hasMany(Tour::class);} }
