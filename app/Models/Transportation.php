<?php
namespace App\Models;
use App\Models\Concerns\ResolvesMediaUrls;
use Astrotomic\Translatable\Contracts\Translatable as Contract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Transportation extends Model implements Contract { use Translatable, SoftDeletes, ResolvesMediaUrls; public array $translatedAttributes=['name','description']; protected $fillable=['type','seat_count','image','is_active','sort_order']; protected $casts=['is_active'=>'boolean']; public function tours(){return $this->belongsToMany(Tour::class,'tour_transportation');} public function getImageAttribute(?string $value): ?string { return $this->mediaUrl($value); } }
