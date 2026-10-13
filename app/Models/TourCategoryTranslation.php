<?php
namespace App\Models;
use App\Models\Concerns\GeneratesSlug;use Illuminate\Database\Eloquent\Model;
class TourCategoryTranslation extends Model { use GeneratesSlug; protected $slugSource='name'; public $timestamps=false; protected $fillable=['name','slug','description']; }
