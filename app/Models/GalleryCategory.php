<?php
namespace App\Models;use Astrotomic\Translatable\Contracts\Translatable as Contract;use Astrotomic\Translatable\Translatable;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\SoftDeletes;
class GalleryCategory extends Model implements Contract{use Translatable,SoftDeletes;public array $translatedAttributes=['name','slug'];protected $fillable=['is_active','sort_order'];protected $casts=['is_active'=>'boolean'];public function albums(){return $this->hasMany(GalleryAlbum::class);}}
