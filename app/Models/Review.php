<?php

namespace App\Models;

use App\Models\Concerns\ResolvesMediaUrls;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, Translatable, SoftDeletes, ResolvesMediaUrls;

    public $translatedAttributes = ['name', 'position', 'content'];
    protected $fillable = ['tour_id', 'image', 'email', 'travel_date', 'rating', 'status', 'is_active', 'order'];

    public const STATUSES = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    protected $casts = [
        'is_active' => 'boolean',
        'travel_date' => 'date',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function tour()
    {
        return $this->belongsTo(Tour::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    public function getImageAttribute(?string $value): ?string
    {
        return $this->mediaUrl($value);
    }
}
