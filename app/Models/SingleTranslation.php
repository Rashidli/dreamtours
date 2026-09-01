<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SingleTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['title', 'seo_title', 'seo_description', 'seo_keywords', 'slug'];
}
