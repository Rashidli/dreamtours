<?php
namespace App\Models;use Astrotomic\Translatable\Contracts\Translatable as Contract;use Astrotomic\Translatable\Translatable;use Illuminate\Database\Eloquent\Model;
class GalleryMedia extends Model implements Contract{use Translatable;public array $translatedAttributes=['alt_text','caption'];protected $table='gallery_media';protected $fillable=['gallery_album_id','type','path','thumbnail','sort_order'];public function album(){return $this->belongsTo(GalleryAlbum::class,'gallery_album_id');}}
