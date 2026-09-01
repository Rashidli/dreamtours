<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Blog extends Model
{
    use HasFactory, Translatable, SoftDeletes;

    public $translatedAttributes = [
        'title', 'short_description', 'description', 'slug',
        'img_alt', 'img_title', 'meta_title', 'meta_description', 'meta_keywords'
    ];

    protected $fillable = ['service_id', 'image', 'is_active', 'is_featured', 'view'];

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

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function incrementView()
    {
        $this->increment('view');
    }

    public function getViewsAttribute()
    {
        return $this->view ?? 0;
    }
}
