<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewTranslation extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'position', 'content'];
}
