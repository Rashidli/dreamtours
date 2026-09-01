<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            'home' => ['Home', '/', ['hero', 'featured_tours', 'destinations', 'tour_types', 'why_choose_us', 'reviews', 'gallery_preview', 'cta']],
            'about' => ['About us', 'about', ['hero', 'story', 'statistics', 'values', 'team', 'partners', 'reviews', 'cta']],
            'one-day-tours' => ['One-day tours', 'one-day-tours', ['hero', 'catalogue', 'benefits', 'cta']],
            'multi-day-tours' => ['Multi-day tours', 'multi-day-tours', ['hero', 'catalogue', 'benefits', 'cta']],
            'international-tours' => ['International tours', 'international-tours', ['hero', 'catalogue', 'benefits', 'cta']],
            'private-tours' => ['Private tours', 'private-tours', ['hero', 'process', 'trip_ideas', 'benefits', 'request_form', 'cta']],
            'cruises' => ['Cruises', 'cruises', ['hero', 'catalogue', 'benefits', 'cta']],
            'visa-support' => ['Visa support', 'visa-support', ['hero', 'disclaimer', 'process', 'countries', 'documents', 'faq', 'request_form', 'cta']],
            'gallery' => ['Gallery', 'gallery', ['hero', 'filters', 'albums', 'cta']],
            'contact' => ['Contact', 'contact', ['hero', 'contact_form', 'contact_details', 'map', 'pickup_points', 'faq', 'cta']],
        ];

        $heroes = [
            'home' => ['Explore. Experience. Enjoy.', 'Find Your Next Adventure', 'Day trips, weekend getaways and unforgettable journeys across Ontario, Québec and beyond.', 'Find Tours'],
            'about' => ['About DreamTour', "We Don't Just Plan Trips, We Create Memories.", 'DreamTour is a proudly Canadian tour company based in the Greater Toronto Area. We design comfortable, well-run group travel across Ontario, Québec and beyond — with a personal touch.', null],
            'contact' => ['Get in Touch', "We're Here to Help You Plan Your Next Adventure.", "Questions about our tours, pricing or pick-up points? Send a message or call — we'd love to hear from you.", null],
            'cruises' => ['Cruises', 'Wake Up Somewhere New.', 'Hand-picked sailings from the Caribbean to the fjords — booked on their own, or paired with one of our coach tours and transfers.', null],
            'gallery' => ['Gallery', 'Moments From the Road.', 'Real photos from past departures, grouped by trip. Open an album to flick through — this is where you could be next season.', null],
            'international-tours' => ['International Tours', 'The World, Well Planned.', 'Escorted group trips beyond Canada — Europe, Asia, the Caucasus and the Philippines — with flights, hotels and guiding all arranged.', null],
            'multi-day-tours' => ['Multi-Day Tours', 'More Days. More Places. Unforgettable Journeys.', 'Curated multi-day trips across Ontario, Québec, the Canadian Rockies and over the border — hotels, transfers and guiding sorted.', null],
            'one-day-tours' => ['Day Trips', 'One Day. Endless Discovery.', 'Leave in the morning and be home by evening — fully guided day trips across Ontario and Québec, with pickup close to home.', null],
            'private-tours' => ['Private Tours', 'Your Trip. Your Way. 100% Private.', 'A tour built around your group, your schedule and your interests — your own vehicle, your own guide, no strangers on board.', null],
            'visa-support' => ['Visa Support', 'Travel Paperwork, Handled.', "We build your document checklist, prepare the forms, review every page and book your appointment — so the visa is the one thing you don't have to worry about before your trip.", null],
        ];

        foreach ($pages as $key => $definition) {
            [$title, $slug, $sections] = $definition;
            $order = array_search($key, array_keys($pages), true);
            $page = Page::updateOrCreate(['key' => $key], [
                'template' => $key,
                'sort_order' => $order,
                'is_active' => true,
                'en' => ['title' => $title, 'slug' => $slug, 'meta_title' => $title.' | DreamTour'],
            ]);
            foreach ($sections as $sectionOrder => $sectionKey) {
                $translation = ['title' => str($sectionKey)->replace('_', ' ')->title()->toString()];
                if ($sectionKey === 'hero' && isset($heroes[$key])) {
                    [$eyebrow, $heroTitle, $subtitle, $buttonText] = $heroes[$key];
                    $translation = [
                        'eyebrow' => $eyebrow,
                        'title' => $heroTitle,
                        'subtitle' => $subtitle,
                        'button_text' => $buttonText,
                    ];
                }
                $page->sections()->updateOrCreate(['key' => $sectionKey], [
                    'type' => $sectionKey,
                    'sort_order' => $sectionOrder,
                    'is_active' => true,
                    'en' => $translation,
                ]);
            }
        }
    }
}
