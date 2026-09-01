<?php

namespace App\Http\Controllers;

use App\Models\{ContactItem, NavigationItem, PickupPoint, Setting, Social, Tour};
use Illuminate\Contracts\View\View;

class TourPageController extends Controller
{
    public function show(string $slug): View
    {
        $tour = Tour::query()
            ->where('status', 'published')
            ->whereTranslation('slug', $slug)
            ->with([
                'translations', 'destination.translations', 'categories.translations',
                'transportations.translations', 'pickupPoints.translations',
                'departures' => fn ($query) => $query->whereIn('status', ['available', 'limited'])->orderBy('departs_at'),
                'itineraryDays.translations', 'itineraryDays.items.translations',
                'contentItems.translations', 'galleryAlbums.media.translations',
            ])->firstOrFail();

        $pickupPoints = $tour->pickupPoints->isNotEmpty()
            ? $tour->pickupPoints
            : PickupPoint::where('is_active', true)->with('translations')->orderBy('sort_order')->get();

        return view('front.pages.tour-detail', [
            'tour' => $tour,
            'pickupPoints' => $pickupPoints,
            'siteSettings' => Setting::query()->pluck('value', 'key'),
            'headerNavigation' => NavigationItem::where('location', 'header')->whereNull('parent_id')->where('is_active', true)
                ->with(['translations', 'children' => fn ($query) => $query->where('is_active', true)->with('translations')])->orderBy('sort_order')->get(),
            'footerNavigation' => NavigationItem::whereIn('location', ['footer_company', 'footer_support', 'footer_legal'])
                ->where('is_active', true)->with('translations')->orderBy('sort_order')->get()->groupBy('location'),
            'contactItems' => ContactItem::active()->ordered()->with('translations')->get(),
            'socials' => Social::active()->ordered()->get(),
        ]);
    }
}
