<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, Translatable, SoftDeletes;

    public $translatedAttributes = [
        'title', 'short_description', 'description', 'slug',
        'img_alt', 'img_title', 'meta_title', 'meta_description', 'meta_keywords'
    ];

    protected $fillable = ['image', 'icon', 'is_active', 'is_featured', 'order'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }

    public function portfolios()
    {
        return $this->hasMany(Portfolio::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }
}
