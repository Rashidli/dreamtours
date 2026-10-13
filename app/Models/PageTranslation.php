<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSlug;
use Illuminate\Database\Eloquent\Model;

class PageTranslation extends Model
{
    use GeneratesSlug;

    public $timestamps = false;
    protected $fillable = ['title', 'slug', 'meta_title', 'meta_description', 'meta_keywords'];
}
