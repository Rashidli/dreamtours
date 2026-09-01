<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class About extends Model
{
    use HasFactory, Translatable;

    public $translatedAttributes = ['title', 'description'];
    protected $fillable = ['image', 'key'];

    public static function getByKey($key)
    {
        return self::where('key', $key)->first();
    }
}
