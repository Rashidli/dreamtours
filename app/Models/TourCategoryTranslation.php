<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TourCategoryTranslation extends Model { public $timestamps=false; protected $fillable=['name','slug','description']; }
