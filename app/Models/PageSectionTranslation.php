<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageSectionTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['eyebrow', 'title', 'subtitle', 'body', 'button_text'];
}
