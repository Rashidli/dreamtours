<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Portfolio extends Model
{
    use HasFactory, Translatable, SoftDeletes;

    public $translatedAttributes = [
        'title', 'short_description', 'description', 'slug',
        'img_alt', 'img_title', 'meta_title', 'meta_description'
    ];

    protected $fillable = ['service_id', 'client', 'location', 'image', 'is_active', 'is_featured', 'order'];

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

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function images()
    {
        return $this->hasMany(PortfolioImage::class)->orderBy('order');
    }
}
