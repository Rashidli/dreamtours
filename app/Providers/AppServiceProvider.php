<?php

namespace App\Providers;

use App\Models\Destination;
use App\Models\Tour;
use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        // The footer lists tours and destinations on every page, including the
        // tour detail page, which is not served with the catalogue payload.
        View::composer('front.partials.footer', function ($view) {
            $view->with([
                'footerTours' => Tour::query()
                    ->where('status', 'published')
                    ->with(['translations', 'departures' => fn ($q) => $q->where('departs_at', '>=', now())->orderBy('departs_at')])
                    ->orderByDesc('show_on_home')
                    ->orderBy('sort_order')
                    ->take(5)
                    ->get(),
                'footerDestinations' => Destination::query()
                    ->where('is_active', true)
                    ->withCount(['tours' => fn ($q) => $q->where('status', 'published')])
                    ->with('translations')
                    ->orderByDesc('tours_count')
                    ->orderBy('sort_order')
                    ->take(6)
                    ->get()
                    ->each(function (Destination $destination) {
                        // Open the listing page that holds most of this destination's tours.
                        $slug = Tour::where('status', 'published')->whereHas('destination', fn ($q) => $q->whereKey($destination->id)->orWhere('parent_id', $destination->id))
                            ->with('categories.translations')->get()
                            ->flatMap(fn (Tour $tour) => $tour->categories->pluck('slug'))
                            ->intersect(['one-day', 'multi-day', 'international', 'cruises'])
                            ->countBy()->sortDesc()->keys()->first();
                        $destination->listing_url = ['multi-day' => '/multi-day-tours', 'international' => '/international-tours', 'cruises' => '/cruises'][$slug] ?? '/one-day-tours';
                    }),
            ]);
        });
    }
}
