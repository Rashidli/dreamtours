<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class CmsSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            'home' => ['Home', '/', ['hero', 'featured_tours', 'tour_types', 'destinations', 'why_choose_us', 'departures', 'reviews', 'gallery_preview', 'request_form', 'pickup_points', 'cta']],
            'about' => ['About us', 'about', ['hero', 'story', 'values', 'team', 'partners', 'reviews', 'cta']],
            'one-day-tours' => ['One-day tours', 'one-day-tours', ['hero', 'catalogue', 'benefits', 'cta']],
            'multi-day-tours' => ['Multi-day tours', 'multi-day-tours', ['hero', 'catalogue', 'benefits', 'cta']],
            'international-tours' => ['International tours', 'international-tours', ['hero', 'catalogue', 'cta']],
            'private-tours' => ['Private tours', 'private-tours', ['hero', 'benefits', 'trip_ideas', 'process', 'request_form', 'cta']],
            'cruises' => ['Cruises', 'cruises', ['hero', 'catalogue', 'cta']],
            'visa-support' => ['Visa support', 'visa-support', ['hero', 'benefits', 'process', 'countries', 'documents', 'faq', 'request_form', 'cta']],
            'gallery' => ['Gallery', 'gallery', ['hero', 'cta']],
            'contact' => ['Contact', 'contact', ['hero', 'contact_form', 'contact_details', 'pickup_points', 'faq', 'cta']],
            'favorites' => ['My favourites', 'favorites', ['hero']],
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
            'favorites' => [null, 'My Favourites', 'The tours and cruises you saved with the heart button. They are kept in this browser on this device — they are not tied to an account.', null],
            'visa-support' => ['Visa Support', 'Travel Paperwork, Handled.', "We build your document checklist, prepare the forms, review every page and book your appointment — so the visa is the one thing you don't have to worry about before your trip.", null],
        ];

        // Copy lifted out of the blade templates, so every heading on the site is editable in the admin.
        $copy = [
            'home' => [
                'featured_tours' => ['eyebrow' => 'Upcoming Departures', 'title' => 'Upcoming Tours', 'subtitle' => 'Reserve your seat on our next departures. Dates, prices and availability are updated regularly.'],
                'tour_types' => ['eyebrow' => 'Browse by Category', 'title' => 'Find the Right Kind of Trip', 'subtitle' => 'Choose an admin-managed tour category and explore available journeys.'],
                'destinations' => ['eyebrow' => 'Where People Go', 'title' => 'Popular Destinations', 'subtitle' => 'The regions our travellers keep coming back to — across Ontario, Québec and just over the border.'],
                'why_choose_us' => ['eyebrow' => 'Why DreamTour', 'title' => 'Travel Made Easy & Enjoyable', 'subtitle' => 'We handle the planning, the driving and the details — you just show up and enjoy the trip.'],
                'departures' => ['eyebrow' => 'Plan Ahead', 'title' => 'Upcoming Departure Dates', 'subtitle' => 'The next few departures at a glance. Seats are confirmed on a first-come basis.'],
                'reviews' => ['eyebrow' => 'Traveller Reviews', 'title' => 'Trips People Loved'],
                'gallery_preview' => ['eyebrow' => 'Gallery', 'title' => 'Moments From Past Trips', 'subtitle' => 'Real photos from recent departures — this is where you could be next season.'],
                'pickup_points' => ['eyebrow' => 'Pick-up Points', 'title' => 'Convenient Locations Across the GTA', 'subtitle' => 'Choose the stop nearest you when you book. Most tours also offer a central downtown departure.'],
                'request_form' => ['eyebrow' => 'Quick Request', 'title' => 'Not Sure Which Tour? Ask Us.', 'subtitle' => 'Send a quick request and a DreamTour representative will call or message you to help plan the trip — no obligation.'],
                'cta' => ['title' => 'Have questions? We\'re here to help.', 'subtitle' => 'Call or message us any day — 8:00 AM to 8:00 PM.'],
            ],
            'about' => [
                'story' => ['eyebrow' => 'Our Story', 'title' => 'Built on Passion. Driven by People.'],
                'values' => ['eyebrow' => 'Why Travel With DreamTour', 'title' => 'More Than Just a Tour Company', 'subtitle' => 'We handle the planning, the driving and the details — you just show up and enjoy the trip.'],
                'team' => ['eyebrow' => 'Meet the Team', 'title' => 'The People Behind Your Adventures', 'subtitle' => 'A small team that plans every trip and, more often than not, comes along for it.'],
                'partners' => ['eyebrow' => 'Licensed & Accredited', 'title' => 'Book With Confidence', 'subtitle' => 'DreamTour is a registered Ontario travel retailer. Payments made for your trip are protected under the Ontario Travel Industry Compensation Fund.'],
                'reviews' => ['eyebrow' => 'What Our Travellers Say', 'title' => 'Real Experiences. Happy Travellers.'],
                'cta' => ['title' => 'Have questions? We\'re here to help.', 'subtitle' => 'Call or message us any day — 8:00 AM to 8:00 PM.'],
            ],
            'contact' => [
                'contact_form' => ['eyebrow' => 'Send Us a Message', 'title' => 'We\'d Love to Hear From You', 'subtitle' => 'Fill out the form and our team will get back to you shortly. Every message is logged as a request so nothing gets missed.'],
                'contact_details' => ['eyebrow' => 'Contact Information', 'title' => 'Reach Us Anytime'],
                'pickup_points' => ['eyebrow' => 'Popular Pick-up Points', 'title' => 'Convenient Locations Across the GTA', 'subtitle' => 'Choose the stop nearest you when you book. Most tours also offer a central downtown departure.'],
                'faq' => ['eyebrow' => 'FAQ', 'title' => 'Frequently Asked Questions'],
                'cta' => ['title' => 'Need help right now?', 'subtitle' => 'Call or message us any day — 8:00 AM to 8:00 PM.'],
            ],
            'private-tours' => [
                'benefits' => ['eyebrow' => 'Why Choose Private Tours', 'title' => 'Travel On Your Terms, In Total Comfort', 'subtitle' => 'All the planning and driving handled — just your group, going where you want to go.'],
                'trip_ideas' => ['eyebrow' => 'Popular Private Tour Ideas', 'title' => 'Inspiration For Your Next Adventure', 'subtitle' => 'Start from one of these or bring your own idea — every private tour is built from scratch.'],
                'process' => ['eyebrow' => 'How It Works', 'title' => 'Simple Steps to Your Perfect Trip'],
                'request_form' => ['eyebrow' => 'Plan Your Private Tour', 'title' => 'Tell Us What You Have in Mind', 'subtitle' => 'Send the basics and a DreamTour planner will come back with an itinerary and a quote — usually within one business day. No cost, no commitment.'],
                'cta' => ['title' => 'Have questions? We\'re here to help.', 'subtitle' => 'Call or message us any day — 8:00 AM to 8:00 PM.'],
            ],
            'visa-support' => [
                'benefits' => ['eyebrow' => 'What You Get', 'title' => 'What\'s Included in Visa Support', 'subtitle' => 'One flat service fee per applicant, per destination. Consular and visa-centre fees are billed separately at cost.'],
                'process' => ['eyebrow' => 'The Process', 'title' => 'How Visa Support Works', 'subtitle' => 'Four steps from first enquiry to a decision — most of the work is on us.'],
                'countries' => ['eyebrow' => 'Where We Help', 'title' => 'Countries We Assist With', 'subtitle' => 'Processing times are the consulate\'s current estimates, not a promise. Don\'t see your destination? Ask us — the list keeps growing.'],
                'documents' => ['eyebrow' => 'The Checklist', 'title' => 'Documents You\'ll Usually Need', 'subtitle' => 'This is the common core. Your personalised checklist may add or drop items depending on your nationality, your status in Canada and the destination.'],
                'faq' => ['eyebrow' => 'Questions', 'title' => 'Visa Support FAQ'],
                'request_form' => ['eyebrow' => 'Start a Request', 'title' => 'Tell Us About Your Trip', 'subtitle' => 'Send the basics and a DreamTour advisor will reply within one business day with your document checklist and a full quote, including all government fees.'],
                'cta' => ['title' => 'Not sure if you need a visa? Ask us first.', 'subtitle' => 'Eligibility checks are free — call or message any day, 8:00 AM to 8:00 PM.'],
            ],
            'one-day-tours' => [
                'catalogue' => ['title' => 'One-Day Tours'],
                'benefits' => ['eyebrow' => 'Why DreamTour', 'title' => 'Travel Made Easy & Enjoyable', 'subtitle' => 'We handle the planning, the driving and the details — you just show up and enjoy the trip.'],
                'cta' => ['title' => 'Not sure which day trip to pick?', 'subtitle' => 'Call or message us any day — 8:00 AM to 8:00 PM.'],
            ],
            'multi-day-tours' => [
                'catalogue' => ['title' => 'Multi-Day Tours'],
                'benefits' => ['eyebrow' => 'Why DreamTour', 'title' => 'Everything You Need. So You Can Enjoy the Journey.', 'subtitle' => 'We handle the planning, the driving and the details — you just show up and enjoy the trip.'],
                'cta' => ['title' => 'Planning a longer trip? Let\'s talk it through.', 'subtitle' => 'Call or message us any day — 8:00 AM to 8:00 PM.'],
            ],
            'cruises' => [
                'catalogue' => ['title' => 'Cruises'],
                'cta' => ['title' => 'Want a cabin recommendation? Just ask.', 'subtitle' => 'Call or message us any day — 8:00 AM to 8:00 PM.'],
            ],
            'gallery' => [
                'cta' => ['title' => 'Travelled with us? We\'d love your photos.'],
            ],
            'international-tours' => [
                'catalogue' => ['title' => 'International Tours'],
                'cta' => ['title' => 'Dreaming of a trip abroad? Let\'s plan it.', 'subtitle' => 'Call or message us any day — 8:00 AM to 8:00 PM.'],
            ],
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
                $translation = $copy[$key][$sectionKey]
                    ?? ['title' => str($sectionKey)->replace('_', ' ')->title()->toString()];
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
