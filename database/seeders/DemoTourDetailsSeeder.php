<?php

namespace Database\Seeders;

use App\Models\About;
use App\Models\CompanyContent;
use App\Models\Destination;
use App\Models\GalleryAlbum;
use App\Models\GalleryCategory;
use App\Models\PickupPoint;
use App\Models\Review;
use App\Models\Setting;
use App\Models\Tour;
use App\Models\TourCategory;
use App\Models\Transportation;
use Illuminate\Database\Seeder;

/**
 * Demonstration content so every tour page is complete: departures with room/child pricing,
 * day-by-day itineraries, inclusions with icons, policies, galleries and per-tour reviews.
 * Safe to run repeatedly — each tour's generated content is replaced, not duplicated.
 * Replace it with the real trip details through the admin panel.
 */
class DemoTourDetailsSeeder extends Seeder
{
    private const IMG = 'https://images.unsplash.com/photo-%s?q=80&w=1400&auto=format&fit=crop';
    private const REVIEW_DOMAIN = '@demo.dreamtour.test';

    public function run(): void
    {
        $categories = TourCategory::with('translations')->get()->keyBy('slug');
        $destinations = Destination::with('translations')->get()->keyBy('slug');
        $coach = Transportation::where('type', 'coach')->first();
        $pickupIds = PickupPoint::where('is_active', true)->orderBy('sort_order')->pluck('id');
        $galleryCategory = GalleryCategory::whereTranslation('slug', 'past-tours')->first();

        foreach ($this->tours() as $sort => $data) {
            $tour = Tour::whereTranslation('slug', $data['slug'])->first() ?? new Tour;
            $tour->fill([
                'destination_id' => $destinations[$data['destination']]->id,
                'main_image' => $this->img($data['images'][0]),
                'duration_days' => $data['days'],
                'duration_nights' => $data['days'] - 1,
                'age_restriction' => $data['age'] ?? 'All ages welcome',
                'activity_level' => $data['activity'],
                'deposit_per_person' => $data['deposit'],
                'is_private' => $data['private'] ?? false,
                'is_cruise' => $data['cruise'] ?? false,
                'show_on_home' => $data['home'] ?? true,
                'badge' => $data['badge'] ?? null,
                'status' => 'published',
                'sort_order' => $sort,
                'en' => [
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'short_description' => $data['short'],
                    'description' => $data['description'],
                ] + $this->policies($data),
            ])->save();

            $tour->categories()->sync($categories->whereIn('slug', $data['categories'])->pluck('id'));
            if ($coach && empty($data['cruise'])) $tour->transportations()->syncWithoutDetaching([$coach->id]);
            $tour->pickupPoints()->sync($pickupIds);

            // Departures: every departure of this tour is priced in CAD with the tour's price list.
            foreach ($data['departures'] as [$date, $reserved]) {
                // Match on the day, so an existing departure on that date is updated rather than duplicated.
                $departure = $tour->departures()->whereDate('departs_at', $date)->first() ?? $tour->departures()->make();
                $departure->fill([
                    'departs_at' => $date.' '.($data['start'] ?? '07:00:00'),
                    'returns_at' => date('Y-m-d', strtotime($date.' +'.($data['days'] - 1).' days')).' '.($data['end'] ?? '21:00:00'),
                    'price' => min($data['prices']),
                    'currency' => 'CAD',
                    'capacity' => $data['capacity'] ?? 56,
                    'reserved_seats' => $reserved,
                    'status' => $reserved >= ($data['capacity'] ?? 56) - 6 ? 'limited' : 'available',
                ])->save();
            }
            foreach ($tour->departures()->get() as $departure) {
                $departure->update(['currency' => 'CAD']);
                $departure->prices()->delete();
                $i = 0;
                foreach ($data['prices'] as $code => $price) {
                    $departure->prices()->create(['code' => $code, 'price' => $price, 'sort_order' => $i++]);
                }
            }

            // Itinerary.
            $tour->itineraryDays()->delete();
            foreach ($data['itinerary'] as $dayIndex => $day) {
                $dayModel = $tour->itineraryDays()->create([
                    'day_number' => $dayIndex + 1,
                    'sort_order' => $dayIndex,
                    'en' => ['title' => $day['title'], 'description' => $day['summary'] ?? null],
                ]);
                foreach ($day['stops'] as $stopIndex => $stop) {
                    $dayModel->items()->create([
                        'arrival_time' => $stop[0],
                        'duration_minutes' => $stop['minutes'] ?? null,
                        'address' => $stop['address'] ?? null,
                        'map_url' => isset($stop['address']) ? 'https://maps.google.com/?q='.urlencode($stop['address']) : null,
                        'images' => isset($stop['image']) ? [$this->img($stop['image'])] : null,
                        'entrance_included' => $stop['ticket'] ?? false,
                        'is_optional' => $stop['optional'] ?? false,
                        'own_expense' => $stop['own'] ?? false,
                        'sort_order' => $stopIndex,
                        'en' => [
                            'location_name' => $stop[1],
                            'description' => $stop[2] ?? null,
                            'sights' => $stop['sights'] ?? null,
                            'activities' => $stop['activities'] ?? null,
                            'notes' => $stop['notes'] ?? null,
                        ],
                    ]);
                }
            }

            // Included / excluded and the additional information blocks.
            $tour->contentItems()->delete();
            $i = 0;
            foreach ($data['included'] as [$icon, $title, $description]) {
                $tour->contentItems()->create(['type' => 'included', 'icon' => $icon, 'sort_order' => $i++, 'en' => ['title' => $title, 'description' => $description]]);
            }
            foreach ($data['excluded'] as [$icon, $title, $description]) {
                $tour->contentItems()->create(['type' => 'excluded', 'icon' => $icon, 'sort_order' => $i++, 'en' => ['title' => $title, 'description' => $description]]);
            }
            foreach ($this->extras($data) as [$type, $title, $description]) {
                $tour->contentItems()->create(['type' => $type, 'sort_order' => $i++, 'en' => ['title' => $title, 'description' => $description]]);
            }

            // Gallery from previous departures of the same trip.
            GalleryAlbum::where('tour_id', $tour->id)->whereTranslation('description', 'Demo album')->get()->each->forceDelete();
            $album = GalleryAlbum::create([
                'gallery_category_id' => $galleryCategory?->id,
                'tour_id' => $tour->id,
                'destination_id' => $tour->destination_id,
                'tour_date' => $data['past_date'],
                'cover_image' => $this->img($data['images'][0]),
                'is_active' => true,
                'sort_order' => $sort,
                'en' => ['title' => $data['name'].' — '.date('F Y', strtotime($data['past_date'])), 'description' => 'Demo album'],
            ]);
            foreach ($data['images'] as $order => $image) {
                $album->media()->create([
                    'type' => 'image',
                    'path' => $this->img($image),
                    'sort_order' => $order,
                    'en' => ['alt_text' => $data['name'], 'caption' => $data['name']],
                ]);
            }

            // Reviews written by passengers of this trip.
            Review::where('tour_id', $tour->id)->where('email', 'like', '%'.self::REVIEW_DOMAIN)->get()->each->forceDelete();
            foreach ($data['reviews'] as $order => [$name, $city, $rating, $text, $status]) {
                Review::create([
                    'tour_id' => $tour->id,
                    'email' => str($name)->slug()->append(self::REVIEW_DOMAIN)->toString(),
                    'travel_date' => $data['past_date'],
                    'rating' => $rating,
                    'status' => $status,
                    'is_active' => $order === 0 && $status === 'approved',
                    'order' => $order,
                    'en' => ['name' => $name, 'position' => $city, 'content' => $text],
                ]);
            }
        }

        // The generic seeded testimonials are superseded by the per-tour reviews.
        Review::whereNull('tour_id')->whereHas('translations', fn ($q) => $q->whereIn('name', ['Sarah M.', 'Michael T.']))->update(['is_active' => false]);

        $this->heroSlides();
        $this->aboutContent();
        $this->settings();
    }

    private function img(string $id): string
    {
        return sprintf(self::IMG, $id);
    }

    private function policies(array $data): array
    {
        $deposit = number_format($data['deposit'], 0);

        return [
            'deposit_policy' => "A non-refundable deposit of CA\${$deposit} per person secures your seat. Your booking is confirmed once the deposit is received and verified. The remaining balance is due 14 days before departure; bookings made within 14 days of departure must be paid in full.",
            'cancellation_policy' => "Cancellations 30 or more days before departure: full refund minus the deposit.\nCancellations 15–29 days before departure: 50% of the tour price is refundable.\nCancellations 14 days or less before departure, or no-shows: no refund.\nYou may transfer your seat to another person free of charge up to 48 hours before departure.",
            'refund_policy' => 'Approved refunds are returned to the original payment method within 7–10 business days. If DreamTour cancels a departure, you receive a full refund including the deposit, or you may move to another date.',
            'boarding_rules' => 'Please arrive at your pickup point 15 minutes before the scheduled time. The coach cannot wait for late passengers. Your seat number is sent 1–2 days before departure; please keep to your assigned seat.',
            'baggage_rules' => $data['days'] > 1
                ? 'One medium suitcase (up to 23 kg) stored under the coach and one small bag on board per passenger.'
                : 'One small day bag per passenger. Large luggage cannot be carried on day tours.',
            'important_notes' => 'The itinerary may be adjusted for weather, traffic or attraction schedules. Prices are per person in Canadian dollars and are subject to applicable taxes.',
        ];
    }

    private function extras(array $data): array
    {
        $rows = [
            ['what_to_bring', 'Comfortable walking shoes', 'Most stops include some walking on uneven ground.'],
            ['what_to_bring', 'Weather-appropriate layers', 'Temperatures change quickly near the water — bring a light jacket.'],
            ['what_to_bring', 'Phone charger & power bank', 'USB charging is available on most coaches.'],
            ['document_requirement', 'Government-issued photo ID', 'Carry the ID that matches the name on your booking.'],
            ['children', 'Children under 12 travel at the child rate', 'Children must be accompanied by an adult. Car seats are not provided.'],
            ['seniors', 'Senior-friendly pace', 'Let us know about limited mobility when booking so we can reserve seats near the front.'],
            ['faq', 'When will I receive my seat number?', 'Seat numbers are assigned after full payment and sent by email 1–2 days before departure.'],
            ['faq', 'Can I choose my pickup point?', 'Yes — choose any listed pickup point during booking. Times differ slightly by location.'],
            ['faq', 'Is the tour guaranteed to run?', 'Departures run with a minimum number of passengers. If a departure is cancelled you receive a full refund.'],
        ];
        if (!empty($data['passport'])) {
            $rows[] = ['document_requirement', 'Valid passport', 'Your passport must be valid for the entire trip. Non-Canadian citizens may need a U.S. visa or ESTA.'];
        }
        if ($data['days'] > 1) {
            $rows[] = ['what_to_bring', 'Overnight essentials', 'Toiletries, medication and a change of clothes for each night.'];
        }

        return $rows;
    }

    private function heroSlides(): void
    {
        CompanyContent::where('type', 'hero_slide')->get()->each->forceDelete();
        foreach ([
            ['1501785888041-af3ef285b470', 'Tobermory', 'Turquoise waters of Georgian Bay', '/tours/tobermory-georgian-bay'],
            ['1489447068241-b3490214e879', 'Niagara Falls', 'Feel the spray of the Horseshoe Falls', '/tours/niagara-falls-niagara-on-the-lake'],
            ['1473448912268-2022ce9509d8', 'Algonquin Park', 'Fall colours on the Lake of Two Rivers', '/tours/algonquin-park-fall-colours'],
            ['1527684651001-731c474bbb5a', 'Agawa Canyon', 'A scenic rail journey through the Canadian Shield', '/tours/agawa-canyon-fall-train'],
            ['1467269204594-9661b134dd2b', 'Québec City', 'Cobblestone streets of Old Québec', '/tours/quebec-city-montmorency'],
            ['1485871981521-5b1fd3805eee', 'New York City', 'Manhattan skyline at golden hour', '/tours/new-york-city'],
            ['1519046904884-53103b34b206', 'Western Caribbean', 'White-sand beaches and warm turquoise sea', '/tours/western-caribbean-cruise'],
        ] as $i => [$image, $place, $caption, $url]) {
            CompanyContent::create([
                'type' => 'hero_slide', 'image' => $this->img($image), 'url' => $url, 'is_active' => true, 'sort_order' => $i,
                'en' => ['title' => $place, 'subtitle' => $caption],
            ]);
        }
    }

    private function aboutContent(): void
    {
        foreach ([
            'vision' => ['Our Vision', '<p>To be the most trusted travel companion for families and communities across Canada — the first name people think of when they want to see new places together, comfortably and without stress.</p>'],
            'mission' => ['Our Mission', '<p>To make group travel feel personal: honest prices, carefully planned itineraries, friendly guides and a real person who looks after every passenger from the moment they book until they are safely home.</p>'],
        ] as $key => [$title, $description]) {
            $about = About::firstOrNew(['key' => $key]);
            $about->fill(['en' => ['title' => $title, 'description' => $description]])->save();
        }
    }

    private function settings(): void
    {
        $defaults = [
            'whatsapp_label' => 'Chat with Jenn on WhatsApp',
            'price_tax_note' => '+ TAX',
            'payment_instructions' => "Send your deposit by Interac e-Transfer to payments@dreamtour.ca.\nIn the message, write the main passenger's full name and the tour date.\nTake a screenshot of the e-Transfer confirmation.",
        ];
        foreach ($defaults as $key => $value) {
            if (Setting::where('key', $key)->doesntExist()) Setting::set($key, $value);
        }
    }

    private function tours(): array
    {
        return [
            [
                'name' => 'Tobermory & Georgian Bay', 'slug' => 'tobermory-georgian-bay', 'destination' => 'ontario',
                'categories' => ['one-day'], 'days' => 1, 'badge' => 'popular', 'activity' => 'Moderate — 3–5 km of walking',
                'deposit' => 50, 'start' => '06:30:00', 'end' => '22:00:00', 'past_date' => '2025-08-16',
                'departures' => [['2026-09-27', 41], ['2026-10-04', 22], ['2026-10-11', 8]],
                'prices' => ['adult' => 139, 'child' => 119, 'senior' => 129],
                'images' => ['1501785888041-af3ef285b470', '1476514525535-07fb3b4ae5f1', '1510414842594-a61c69b5ae57', '1447752875215-b2761acb3c5d', '1473448912268-2022ce9509d8', '1542202229-7d93c33f5d07'],
                'short' => 'Crystal-clear turquoise water, the famous Grotto and a glass-bottom boat cruise to Flowerpot Island — Ontario’s most beautiful day trip.',
                'description' => "Spend a full day at the tip of the Bruce Peninsula, where Georgian Bay meets Lake Huron.\n\nWe start with a breakfast stop on the way north, then board a glass-bottom boat to see the 19th-century shipwrecks of Big Tub Harbour before landing on Flowerpot Island. In the afternoon, hike through Bruce Peninsula National Park to the Grotto — a sea cave with water so clear it looks Caribbean.\n\nThe day ends with free time in Little Tub Harbour for ice cream and photos before the drive home.",
                'itinerary' => [[
                    'title' => 'Toronto – Tobermory – Toronto', 'summary' => 'A full day on the Bruce Peninsula with a boat cruise and a hike to the Grotto.',
                    'stops' => [
                        ['06:30', 'Pickup across the GTA', 'Board the coach at your selected pickup point. Please arrive 15 minutes early.'],
                        ['08:30', 'Breakfast stop', 'A 30-minute stop for coffee and breakfast.', 'own' => true, 'minutes' => 30],
                        ['11:30', 'Glass-bottom boat to Flowerpot Island', 'Cruise over the shipwrecks of Big Tub Harbour and land on Flowerpot Island.', 'ticket' => true, 'minutes' => 120, 'address' => 'Little Tub Harbour, Tobermory, ON', 'image' => '1476514525535-07fb3b4ae5f1', 'sights' => 'Sweepstakes shipwreck, Big Tub Lighthouse, flowerpot rock formations', 'activities' => 'Photography, short island trail'],
                        ['14:00', 'The Grotto — Bruce Peninsula National Park', 'Hike 2.5 km through the forest to the famous sea cave and Indian Head Cove.', 'minutes' => 150, 'address' => 'Cyprus Lake Rd, Tobermory, ON', 'image' => '1510414842594-a61c69b5ae57', 'sights' => 'The Grotto, Indian Head Cove, Georgian Bay cliffs', 'activities' => 'Hiking, swimming in summer (bring a towel)', 'notes' => 'The trail includes rocky sections — wear proper shoes.'],
                        ['16:45', 'Free time in Little Tub Harbour', 'Ice cream, souvenirs and dinner on your own.', 'own' => true, 'minutes' => 75],
                        ['18:00', 'Depart for Toronto', 'Relax on the drive back to the GTA.'],
                        ['22:00', 'Arrive back at pickup points', 'End of services.'],
                    ],
                ]],
                'included' => [['🚌', 'Round-trip coach transportation', 'From GTA pickup points'], ['🚢', 'Glass-bottom boat cruise', 'Including the Flowerpot Island drop-off'], ['🎟️', 'National Park entry', 'Bruce Peninsula National Park day pass'], ['🍞', 'Breakfast bun & coffee', 'Served on the coach'], ['💧', 'Bottled water', 'One bottle per passenger'], ['🧑‍✈️', 'Tour leader', 'English-speaking guide all day']],
                'excluded' => [['🍽️', 'Lunch and dinner', 'Plenty of options in Tobermory'], ['💵', 'Tips for the driver and guide', 'Suggested CA$5 per person'], ['🛍️', 'Personal expenses', 'Souvenirs, drinks, snacks']],
                'reviews' => [
                    ['Maria Santos', 'Scarborough', 5, 'The water at the Grotto was unbelievable and Jenn kept everything on schedule. The boat ride over the shipwrecks was the highlight for my kids!', 'approved'],
                    ['Daniel Kim', 'North York', 5, 'Very well organized. Pickup was on time, the coach was clean and the guide gave us lots of tips for the hike.', 'approved'],
                    ['Priya Patel', 'Mississauga', 4, 'Beautiful day. It is a long drive, but the breakfast bun and water on the bus helped. Would book again.', 'approved'],
                    ['Anna Reyes', 'Vaughan', 5, 'Second time with DreamTour — again perfect. Wear good shoes for the Grotto trail!', 'pending'],
                ],
            ],
            [
                'name' => 'Algonquin Park Fall Colours', 'slug' => 'algonquin-park-fall-colours', 'destination' => 'ontario',
                'categories' => ['one-day'], 'days' => 1, 'badge' => 'limited_seats', 'activity' => 'Easy to moderate — short trails',
                'deposit' => 50, 'start' => '07:00:00', 'end' => '21:30:00', 'past_date' => '2025-10-04',
                'departures' => [['2026-09-26', 50], ['2026-10-03', 34]],
                'prices' => ['adult' => 119, 'child' => 99, 'senior' => 109],
                'images' => ['1473448912268-2022ce9509d8', '1507041957456-9c397ce39c97', '1511497584788-876760111969', '1542202229-7d93c33f5d07', '1472214103451-9374bd1c798e', '1441974231531-c6227db76b6e'],
                'short' => 'Ontario’s most famous autumn landscape — red maples, the Lookout Trail and the Lake of Two Rivers at peak colour.',
                'description' => "Every fall, Algonquin Provincial Park turns into a sea of red, orange and gold. This day trip is timed for peak colour.\n\nWalk the Lookout Trail for a panoramic view over the Highway 60 corridor, visit the Algonquin Visitor Centre, and stop at the Lake of Two Rivers for a picnic by the water. On the way home we stop in Huntsville for dinner.",
                'itinerary' => [[
                    'title' => 'Toronto – Algonquin Park – Toronto', 'summary' => 'Peak fall colours along Highway 60.',
                    'stops' => [
                        ['07:00', 'Pickup across the GTA', 'Board at your selected pickup point.'],
                        ['09:00', 'Breakfast stop', 'Coffee and breakfast.', 'own' => true, 'minutes' => 30],
                        ['11:30', 'Lookout Trail', 'A 2 km loop with a spectacular view over the park.', 'ticket' => true, 'minutes' => 90, 'address' => 'Lookout Trail, Algonquin Provincial Park, ON', 'image' => '1507041957456-9c397ce39c97', 'sights' => 'Panoramic view of the fall canopy', 'activities' => 'Hiking, photography'],
                        ['13:15', 'Lake of Two Rivers', 'Picnic lunch by the lake with your Subway sandwich.', 'minutes' => 60, 'image' => '1542202229-7d93c33f5d07'],
                        ['14:30', 'Algonquin Visitor Centre', 'Exhibits on the park’s wildlife and a rooftop viewing deck.', 'minutes' => 60, 'address' => 'Algonquin Visitor Centre, ON'],
                        ['17:30', 'Dinner in Huntsville', 'Choose from restaurants in downtown Huntsville.', 'own' => true, 'minutes' => 75],
                        ['21:30', 'Arrive back at pickup points', 'End of services.'],
                    ],
                ]],
                'included' => [['🚌', 'Round-trip coach transportation', null], ['🎟️', 'Algonquin Park day permit', null], ['🥪', 'Subway sandwich lunch', 'Picnic by the Lake of Two Rivers'], ['💧', 'Bottled water', null], ['🧑‍✈️', 'Tour leader', null]],
                'excluded' => [['🍽️', 'Breakfast and dinner', null], ['💵', 'Tips', 'Suggested CA$5 per person'], ['🛍️', 'Personal expenses', null]],
                'reviews' => [
                    ['Grace Lim', 'Markham', 5, 'We went at exactly the right weekend — the colours were incredible. Loved the Subway picnic by the lake.', 'approved'],
                    ['Robert Chen', 'Richmond Hill', 5, 'Great value. Everything included except dinner, and the guide knew all the best photo spots.', 'approved'],
                    ['Leah Cruz', 'Toronto', 4, 'Lookout Trail is a bit steep but worth it. Very friendly team.', 'approved'],
                ],
            ],
            [
                'name' => 'Niagara Falls & Niagara-on-the-Lake', 'slug' => 'niagara-falls-niagara-on-the-lake', 'destination' => 'ontario',
                'categories' => ['one-day'], 'days' => 1, 'badge' => 'new', 'activity' => 'Easy', 'deposit' => 40,
                'start' => '08:00:00', 'end' => '20:30:00', 'past_date' => '2025-07-19',
                'departures' => [['2026-10-18', 12], ['2026-11-01', 5]],
                'prices' => ['adult' => 99, 'child' => 79, 'senior' => 89],
                'images' => ['1489447068241-b3490214e879', '1433086966358-54859d0ed716', '1500382017468-9049fed747ef', '1465146344425-f00d5f5c8f07', '1518837695005-2083093ee35b'],
                'short' => 'The Horseshoe Falls up close, a boat ride into the mist and a stroll through charming Niagara-on-the-Lake.',
                'description' => "A classic Ontario day out. Feel the spray of the Horseshoe Falls on the Niagara City Cruises boat, walk along the Niagara Parkway and finish in the historic village of Niagara-on-the-Lake with its flower-lined main street and wineries.",
                'itinerary' => [[
                    'title' => 'Toronto – Niagara Falls – Niagara-on-the-Lake – Toronto',
                    'stops' => [
                        ['08:00', 'Pickup across the GTA', 'Board at your selected pickup point.'],
                        ['10:00', 'Niagara City Cruises', 'Sail right to the base of the Horseshoe Falls.', 'ticket' => true, 'minutes' => 60, 'address' => '5920 Niagara Pkwy, Niagara Falls, ON', 'image' => '1433086966358-54859d0ed716'],
                        ['11:30', 'Table Rock & Niagara Parkway', 'Free time for photos and lunch.', 'own' => true, 'minutes' => 120],
                        ['14:00', 'Journey Behind the Falls', 'Walk through tunnels to observation decks behind the falls.', 'optional' => true, 'minutes' => 60, 'notes' => 'Optional — CA$30 per person, paid on site.'],
                        ['15:30', 'Niagara-on-the-Lake', 'Stroll Queen Street, shops and the clock tower.', 'minutes' => 120, 'image' => '1500382017468-9049fed747ef'],
                        ['18:00', 'Wine tasting', 'Tasting of three VQA wines at a local winery.', 'optional' => true, 'minutes' => 45, 'notes' => 'Optional — 19+ only.'],
                        ['20:30', 'Arrive back at pickup points', 'End of services.'],
                    ],
                ]],
                'included' => [['🚌', 'Round-trip coach transportation', null], ['🚢', 'Niagara City Cruises boat ticket', null], ['💧', 'Bottled water', null], ['🍿', 'Light snacks on the coach', null], ['🧑‍✈️', 'Tour leader', null]],
                'excluded' => [['🍽️', 'Lunch is not included', 'Free time for lunch at Table Rock'], ['🎟️', 'Journey Behind the Falls', 'Optional, CA$30'], ['🍷', 'Wine tasting', 'Optional'], ['💵', 'Tips', null]],
                'reviews' => [
                    ['Joy Mendoza', 'Brampton', 5, 'Perfect for our parents who visited from Manila. Easy pace and the boat was amazing.', 'approved'],
                    ['Kevin Brown', 'Etobicoke', 4, 'Good day trip, good price. Niagara-on-the-Lake was lovely.', 'approved'],
                ],
            ],
            [
                'name' => 'Agawa Canyon Fall Train', 'slug' => 'agawa-canyon-fall-train', 'destination' => 'ontario',
                'categories' => ['multi-day'], 'days' => 3, 'badge' => 'new', 'activity' => 'Easy — mostly on the train', 'deposit' => 200,
                'past_date' => '2025-09-27', 'end' => '22:00:00',
                'departures' => [['2026-10-10', 46], ['2026-10-17', 20]],
                'prices' => ['double' => 698, 'triple' => 648, 'quad' => 598, 'child' => 499],
                'images' => ['1527684651001-731c474bbb5a', '1470071459604-3b5ec3a7fe05', '1469474968028-56623f02e42e', '1433086966358-54859d0ed716', '1511497584788-876760111969', '1473448912268-2022ce9509d8'],
                'short' => 'Three days in Northern Ontario with the legendary Agawa Canyon Tour Train through blazing fall forests, waterfalls and the Canadian Shield.',
                'description' => "One of the most scenic rail journeys in North America. From Sault Ste. Marie, the Agawa Canyon Tour Train climbs 114 miles into the wilderness over trestles and along lakes, arriving in a canyon floor surrounded by waterfalls.\n\nThe trip includes two nights of hotel accommodation, breakfast every morning, a stop at the Big Nickel in Sudbury and a lakeside visit to Batchawana Bay.",
                'itinerary' => [
                    ['title' => 'Toronto – Sudbury – Sault Ste. Marie', 'summary' => 'Scenic drive north along Georgian Bay.', 'stops' => [
                        ['07:00', 'Pickup across the GTA', 'Board with your luggage.'],
                        ['10:30', 'Parry Sound lookout', 'Short photo stop over Georgian Bay.', 'minutes' => 30],
                        ['12:30', 'Lunch in Sudbury & Big Nickel', 'Photo stop at the famous Big Nickel.', 'own' => true, 'minutes' => 75, 'address' => 'Big Nickel, Sudbury, ON'],
                        ['18:00', 'Hotel check-in, Sault Ste. Marie', 'Evening at leisure.', 'notes' => 'Overnight in Sault Ste. Marie.'],
                    ]],
                    ['title' => 'Agawa Canyon Tour Train', 'summary' => 'Full-day rail journey into the canyon.', 'stops' => [
                        ['06:30', 'Breakfast at the hotel', null],
                        ['08:00', 'Board the Agawa Canyon Tour Train', 'Reserved coach seating with panoramic windows and GPS-triggered commentary.', 'ticket' => true, 'minutes' => 240, 'image' => '1527684651001-731c474bbb5a', 'sights' => 'Montreal River trestle, Lake Superior highlands, fall canopy'],
                        ['12:00', 'Agawa Canyon Park', '90-minute stop to walk to Bridal Veil and Black Beaver falls.', 'minutes' => 90, 'image' => '1433086966358-54859d0ed716', 'activities' => 'Waterfall trails, Lookout Trail (300 steps)'],
                        ['13:30', 'Lunch on board', 'Dining car available.', 'own' => true],
                        ['18:00', 'Return to Sault Ste. Marie', 'Overnight in Sault Ste. Marie.'],
                    ]],
                    ['title' => 'Batchawana Bay – Toronto', 'stops' => [
                        ['07:00', 'Breakfast and check-out', null],
                        ['09:00', 'Batchawana Bay', 'Walk along the sandy shore of Lake Superior.', 'minutes' => 60, 'image' => '1470071459604-3b5ec3a7fe05'],
                        ['13:00', 'Lunch stop', null, 'own' => true, 'minutes' => 60],
                        ['22:00', 'Arrive back in the GTA', 'End of services.'],
                    ]],
                ],
                'included' => [['🚌', 'Coach transportation for 3 days', null], ['🚆', 'Agawa Canyon Tour Train ticket', 'Reserved coach seat'], ['🏨', '2 nights hotel accommodation', 'Sault Ste. Marie, 3-star'], ['☕', '2 hotel breakfasts', null], ['💧', 'Bottled water daily', null], ['🧑‍✈️', 'Tour leader', null]],
                'excluded' => [['🍽️', 'Lunches and dinners', 'Lunch on the train is not included'], ['💵', 'Tips', 'Suggested CA$5 per person, per day'], ['🛍️', 'Personal expenses', null], ['🛡️', 'Travel insurance', 'Strongly recommended']],
                'reviews' => [
                    ['Linda Ferreira', 'Woodbridge', 5, 'Bucket-list trip! The colours from the train were unreal. Hotel was comfortable and the room pricing for our group of four was great value.', 'approved'],
                    ['Mark & Susan Taylor', 'Oshawa', 5, 'Everything organized perfectly — we just enjoyed the view. Loved Batchawana Bay on the last day.', 'approved'],
                    ['Rizwan Ahmed', 'Mississauga', 4, 'Long drive but worth it. Bring snacks for the train.', 'approved'],
                ],
            ],
            [
                'name' => 'Québec City & Montmorency Falls', 'slug' => 'quebec-city-montmorency', 'destination' => 'quebec',
                'categories' => ['multi-day'], 'days' => 2, 'activity' => 'Moderate — walking on hills and cobblestones', 'deposit' => 150,
                'past_date' => '2025-10-11', 'end' => '23:00:00',
                'departures' => [['2026-10-15', 18], ['2026-10-24', 0]],
                'prices' => ['double' => 449, 'triple' => 419, 'quad' => 399, 'child' => 329],
                'images' => ['1467269204594-9661b134dd2b', '1519178614-68673b201f36', '1433086966358-54859d0ed716', '1482192505345-5655af888cc4', '1541343672885-9be56236302a'],
                'short' => 'Walk the fortified streets of Old Québec, see Château Frontenac and stand above Montmorency Falls — taller than Niagara.',
                'description' => "A weekend in North America’s most European city. Explore the UNESCO-listed Old Town on a guided walking tour, ride the funicular to Petit-Champlain, and cross the suspension bridge over Montmorency Falls.\n\nIncludes one night at a hotel in Québec City with breakfast.",
                'itinerary' => [
                    ['title' => 'Toronto – Québec City', 'stops' => [
                        ['07:00', 'Pickup across the GTA', null],
                        ['12:00', 'Lunch stop in Kingston', null, 'own' => true, 'minutes' => 60],
                        ['16:30', 'Old Québec walking tour', 'Château Frontenac, Dufferin Terrace, Place Royale and Petit-Champlain.', 'minutes' => 120, 'address' => 'Château Frontenac, Québec City, QC', 'image' => '1467269204594-9661b134dd2b'],
                        ['19:00', 'Dinner in the Old Town', null, 'own' => true],
                        ['21:00', 'Hotel check-in', 'Overnight in Québec City.'],
                    ]],
                    ['title' => 'Montmorency Falls – Toronto', 'stops' => [
                        ['07:30', 'Breakfast at the hotel', null],
                        ['09:00', 'Montmorency Falls Park', 'Cross the suspension bridge above the 83-metre falls.', 'ticket' => true, 'minutes' => 90, 'image' => '1433086966358-54859d0ed716'],
                        ['10:45', 'Cable car ride', 'Ride down to the base of the falls.', 'optional' => true, 'minutes' => 20, 'notes' => 'Optional — about CA$16.'],
                        ['12:30', 'Lunch in Trois-Rivières', null, 'own' => true, 'minutes' => 60],
                        ['23:00', 'Arrive back in the GTA', 'End of services.'],
                    ]],
                ],
                'included' => [['🚌', 'Coach transportation', null], ['🏨', '1 night hotel accommodation', 'Québec City'], ['☕', 'Hotel breakfast', null], ['🎟️', 'Montmorency Falls park entry', null], ['🧑‍✈️', 'Guided Old Québec walking tour', null], ['💧', 'Bottled water', null]],
                'excluded' => [['🍽️', 'Lunches and dinners', null], ['🎟️', 'Montmorency cable car', 'Optional'], ['💵', 'Tips', 'Suggested CA$5 per person, per day'], ['🛍️', 'Personal expenses', null]],
                'reviews' => [
                    ['Camille Dupont', 'Toronto', 5, 'Such a charming city and the walking tour was full of stories. Great hotel location.', 'approved'],
                    ['Arnel Bautista', 'Scarborough', 5, 'We booked a quad room for our family and it was very affordable. Montmorency Falls was stunning.', 'approved'],
                ],
            ],
            [
                'name' => 'New York City Getaway', 'slug' => 'new-york-city', 'destination' => 'united-states',
                'categories' => ['international', 'multi-day'], 'days' => 3, 'badge' => 'limited_seats', 'passport' => true,
                'activity' => 'Moderate — lots of walking', 'deposit' => 200, 'start' => '06:00:00', 'end' => '23:30:00', 'past_date' => '2025-12-05',
                'departures' => [['2026-11-06', 49], ['2026-12-04', 26]],
                'prices' => ['double' => 699, 'triple' => 649, 'quad' => 599, 'child' => 549],
                'images' => ['1485871981521-5b1fd3805eee', '1496442226666-8d4d0e62e6e9', '1522083165195-3424ed129620', '1534430480872-3498386e7856', '1538970272646-f61fabb3a8a2', '1514565131-fce0801e5785'],
                'short' => 'Times Square, the Statue of Liberty, Brooklyn Bridge and the holiday lights of Manhattan — the Big Apple in three days.',
                'description' => "Three days in the city that never sleeps. We cross the border at Buffalo and spend two full days in Manhattan with a guided city tour, a Statue of Liberty ferry, and plenty of free time for shopping on Fifth Avenue.\n\nIncludes two nights in a New Jersey hotel with easy access to Manhattan.",
                'itinerary' => [
                    ['title' => 'Toronto – New York City', 'stops' => [
                        ['06:00', 'Pickup across the GTA', 'Have your passport ready.'],
                        ['08:30', 'Border crossing at Lewiston–Queenston', null, 'notes' => 'Passport and any required U.S. travel documents are mandatory.'],
                        ['13:00', 'Lunch stop', null, 'own' => true, 'minutes' => 60],
                        ['19:30', 'Times Square evening', 'First look at the lights of Times Square.', 'minutes' => 120, 'image' => '1496442226666-8d4d0e62e6e9'],
                        ['22:00', 'Hotel check-in, New Jersey', 'Overnight in New Jersey.'],
                    ]],
                    ['title' => 'Manhattan Highlights', 'stops' => [
                        ['07:30', 'Breakfast at the hotel', null],
                        ['09:30', 'Statue of Liberty ferry', 'Ferry past Liberty Island and Ellis Island.', 'ticket' => true, 'minutes' => 120, 'address' => 'Battery Park, New York, NY'],
                        ['12:00', 'Wall Street & 9/11 Memorial', 'Guided walk through the Financial District.', 'minutes' => 90],
                        ['14:00', 'Lunch in Chinatown', null, 'own' => true, 'minutes' => 60],
                        ['15:30', 'Brooklyn Bridge walk', 'Walk across for the classic skyline view.', 'minutes' => 60, 'image' => '1522083165195-3424ed129620'],
                        ['18:00', 'Top of the Rock observation deck', null, 'optional' => true, 'minutes' => 75, 'notes' => 'Optional — about US$45.'],
                        ['21:30', 'Return to hotel', 'Overnight in New Jersey.'],
                    ]],
                    ['title' => 'Fifth Avenue – Toronto', 'stops' => [
                        ['08:00', 'Breakfast and check-out', null],
                        ['09:30', 'Rockefeller Center & Fifth Avenue', 'Free time for shopping.', 'own' => true, 'minutes' => 150],
                        ['12:30', 'Depart for Toronto', null],
                        ['23:30', 'Arrive back in the GTA', 'End of services.'],
                    ]],
                ],
                'included' => [['🚌', 'Coach transportation', null], ['🏨', '2 nights hotel accommodation', 'New Jersey'], ['☕', '2 hotel breakfasts', null], ['🚢', 'Statue of Liberty ferry ticket', null], ['🧑‍✈️', 'Guided Manhattan city tour', null], ['💧', 'Bottled water daily', null]],
                'excluded' => [['🍽️', 'Lunches and dinners', null], ['🛂', 'U.S. visa or ESTA fees', null], ['🎟️', 'Top of the Rock', 'Optional'], ['💵', 'Tips', 'Suggested CA$5 per person, per day'], ['🛡️', 'Travel and medical insurance', 'Strongly recommended for the U.S.']],
                'reviews' => [
                    ['Jasmine Garcia', 'North York', 5, 'Our first time in New York and it was magical. The Brooklyn Bridge walk at sunset was the best moment.', 'approved'],
                    ['Tom Wilson', 'Ajax', 4, 'Packed itinerary but you see everything. Border crossing was smooth thanks to the guide’s instructions.', 'approved'],
                    ['Ella Navarro', 'Toronto', 5, 'Booked a triple room with friends and saved a lot. Would do the Christmas departure again!', 'approved'],
                ],
            ],
            [
                'name' => 'Niagara Falls Private Tour', 'slug' => 'niagara-private-tour', 'destination' => 'ontario', 'private' => true, 'home' => false,
                'categories' => ['private'], 'days' => 1, 'activity' => 'Easy — you set the pace', 'deposit' => 100, 'past_date' => '2025-06-15',
                'departures' => [], 'prices' => ['adult' => 0],
                'images' => ['1489447068241-b3490214e879', '1433086966358-54859d0ed716', '1465146344425-f00d5f5c8f07', '1500382017468-9049fed747ef'],
                'short' => 'Niagara on your schedule — a private vehicle and driver for your family or group, with stops chosen by you.',
                'description' => "Perfect for families, visiting relatives and small groups. Choose your pickup time and address, and we build the day around you: the falls, the Floral Clock, wineries or outlet shopping.\n\nPrices depend on group size and vehicle — send a request and we will reply with a quote within one business day.",
                'itinerary' => [[
                    'title' => 'Sample private itinerary', 'stops' => [
                        ['09:00', 'Pickup at your address', 'Anywhere in the GTA.'],
                        ['10:30', 'Horseshoe Falls & Table Rock', null, 'minutes' => 90],
                        ['12:30', 'Lunch with a view of the falls', null, 'own' => true, 'minutes' => 75],
                        ['14:00', 'Floral Clock & Niagara Parkway', null, 'minutes' => 45],
                        ['15:00', 'Niagara-on-the-Lake', null, 'minutes' => 120],
                        ['18:00', 'Drop-off at your address', null],
                    ],
                ]],
                'included' => [['🚌', 'Private vehicle and driver', 'Minivan or coach depending on group size'], ['💧', 'Bottled water', null], ['🅿️', 'Parking and fuel', null]],
                'excluded' => [['🎟️', 'Attraction tickets', 'We can pre-book them for you'], ['🍽️', 'Meals', null], ['💵', 'Tips', null]],
                'reviews' => [
                    ['The Aquino Family', 'Brampton', 5, 'We booked a private tour for Lola’s 80th birthday. The driver was so patient and kind. Thank you, DreamTour!', 'approved'],
                ],
            ],
            [
                'name' => 'Western Caribbean Cruise', 'slug' => 'western-caribbean-cruise', 'destination' => 'united-states', 'cruise' => true,
                'categories' => ['cruises', 'international'], 'days' => 8, 'badge' => 'popular', 'passport' => true, 'capacity' => 24,
                'activity' => 'Easy', 'deposit' => 300, 'start' => '16:00:00', 'end' => '08:00:00', 'past_date' => '2025-02-08', 'home' => false,
                'departures' => [['2026-11-08', 10], ['2027-02-07', 4]],
                'prices' => ['double' => 1299, 'triple' => 1149, 'quad' => 1049],
                'images' => ['1519046904884-53103b34b206', '1548574505-5e239809ee19', '1507525428034-b723cf961d3e', '1544551763-46a013bb70d5', '1559494007-9f5847c49d94', '1518837695005-2083093ee35b'],
                'short' => 'Seven nights from Miami to Cozumel, Roatán and Costa Maya on Royal Caribbean — with a DreamTour host on board.',
                'description' => "Escape the Canadian winter with a group cruise to the Western Caribbean. DreamTour arranges your cabin, port transfers and a group host who travels with you.\n\nSnorkel the reefs of Cozumel, relax on the beaches of Roatán and explore Mayan ruins in Costa Maya. All meals in the main dining room and buffet are included on board.",
                'itinerary' => [
                    ['title' => 'Miami — Embarkation', 'stops' => [['13:00', 'Port of Miami check-in', 'Group transfer from the hotel to the terminal.'], ['16:00', 'Ship departs Miami', 'Welcome drinks with the DreamTour group.']]],
                    ['title' => 'At sea', 'stops' => [['09:00', 'Day at sea', 'Pools, shows and onboard activities.']]],
                    ['title' => 'Cozumel, Mexico', 'stops' => [['08:00', 'Arrive in Cozumel', null], ['09:30', 'Snorkelling excursion', 'Palancar Reef snorkelling.', 'optional' => true, 'minutes' => 180, 'image' => '1544551763-46a013bb70d5', 'notes' => 'Optional shore excursion — book on board.']]],
                    ['title' => 'Roatán, Honduras', 'stops' => [['09:00', 'Beach day at West Bay', null, 'minutes' => 300, 'image' => '1519046904884-53103b34b206']]],
                    ['title' => 'Costa Maya, Mexico', 'stops' => [['08:00', 'Chacchoben Mayan ruins', null, 'optional' => true, 'minutes' => 240]]],
                    ['title' => 'At sea', 'stops' => [['10:00', 'Day at sea', 'Farewell dinner with the group.']]],
                    ['title' => 'At sea', 'stops' => [['10:00', 'Day at sea', null]]],
                    ['title' => 'Miami — Disembarkation', 'stops' => [['08:00', 'Arrive in Miami', 'Group transfer to the airport.']]],
                ],
                'included' => [['🚢', '7-night cruise', 'Cabin of your chosen occupancy'], ['🍽️', 'All meals in main dining room & buffet', null], ['🧾', 'Port fees and taxes', null], ['🚌', 'Port transfers in Miami', null], ['🧑‍✈️', 'DreamTour group host', null]],
                'excluded' => [['✈️', 'Flights to and from Miami', 'We can quote flights on request'], ['🍷', 'Drinks packages', null], ['🎟️', 'Shore excursions', 'Optional'], ['💵', 'Onboard gratuities', 'About US$18 per person, per day'], ['🛡️', 'Travel insurance', 'Required']],
                'reviews' => [
                    ['Cheryl Dela Cruz', 'Toronto', 5, 'Having a DreamTour host on the ship made it so easy for first-time cruisers like us.', 'approved'],
                    ['Paul Martin', 'Pickering', 4, 'Beautiful ports. Book the snorkelling in Cozumel early!', 'approved'],
                ],
            ],
        ];
    }
}
