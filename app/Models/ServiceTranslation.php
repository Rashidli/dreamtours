<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'title', 'short_description', 'description', 'slug',
        'img_alt', 'img_title', 'meta_title', 'meta_description', 'meta_keywords'
    ];
}
