<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SharesFrontLayout;
use App\Models\PickupPoint;
use App\Models\Tour;
use Illuminate\Contracts\View\View;

class TourPageController extends Controller
{
    use SharesFrontLayout;

    public function show(string $slug): View
    {
        $tour = Tour::query()
            ->where('status', 'published')
            ->whereTranslation('slug', $slug)
            ->with([
                'translations', 'destination.translations', 'destination.parent.translations', 'categories.translations',
                'transportations' => fn ($query) => $query->where('is_active', true)->with('translations'), 'pickupPoints.translations',
                'departures' => fn ($query) => $query->visible()->with(['prices', 'pickupPoints.translations'])->orderBy('departs_at'),
                'itineraryDays.translations', 'itineraryDays.items.translations',
                'contentItems.translations', 'galleryAlbums' => fn ($query) => $query->where('is_active', true)->with('media.translations'),
                'reviews' => fn ($query) => $query->published()->with('translations')->latest('travel_date')->latest(),
            ])->firstOrFail();

        $pickupPoints = $tour->pickupPoints->where('is_active', true)->isNotEmpty()
            ? $tour->pickupPoints->where('is_active', true)->values()
            : PickupPoint::where('is_active', true)->with('translations')->orderBy('sort_order')->get();

        // Main image first, then album photos and itinerary photos, without repeats.
        $photos = collect([$tour->main_image])
            ->merge($tour->galleryAlbums->flatMap->media->where('type', 'image')->pluck('path'))
            ->merge($tour->itineraryDays->flatMap->items->flatMap->images)
            ->filter()->unique()->values();

        $bookable = $tour->departures->first(fn ($departure) => $departure->is_bookable);

        return view('front.pages.tour-detail', [
            'tour' => $tour,
            'pickupPoints' => $pickupPoints,
            'photos' => $photos,
            'videos' => $tour->galleryAlbums->flatMap->media->where('type', 'video')->values(),
            'nextDeparture' => $bookable ?? $tour->departures->first(),
            'canBook' => !$tour->is_private && $bookable !== null,
            'rating' => $tour->reviews->isNotEmpty() ? round((float) $tour->reviews->avg('rating'), 1) : null,
        ] + $this->layoutData());
    }
}
