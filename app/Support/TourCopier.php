<?php

namespace App\Support;

use App\Models\Tour;
use Illuminate\Support\Facades\DB;

/**
 * Duplicates a tour so a near-identical trip can be made by changing a few fields.
 *
 * The copy is always a Draft (not on the website, not on the home page or carousel)
 * with its own URL made from the new name and empty SEO texts. Shared photos keep
 * pointing at the same files. Bookings, reviews, staff, gallery albums and past
 * dates are never copied; future dates are copied only when asked, with no seats taken.
 */
class TourCopier
{
    /**
     * @param array{name: string, itinerary?: bool, content?: bool, dates?: bool} $options
     */
    public static function copy(Tour $source, array $options): Tour
    {
        $source->load([
            'translations', 'categories', 'transportations', 'pickupPoints', 'filterOptions', 'inclusions', 'cruiseDetail',
            'itineraryDays.translations', 'itineraryDays.items.translations', 'contentItems.translations',
            'departures' => fn ($q) => $q->where('departs_at', '>', now())->orderBy('departs_at'),
            'departures.prices', 'departures.pickupPoints',
        ]);

        return DB::transaction(function () use ($source, $options) {
            $tour = $source->replicateWithTranslations();
            $tour->status = 'draft';
            $tour->show_on_home = false;
            $tour->show_in_carousel = false;
            foreach ($tour->translations as $translation) {
                $translation->name = $translation->locale === 'en'
                    ? $options['name']
                    : trim($translation->name.' (copy)');
                // A new address and new search texts: two pages must not compete on Google.
                $translation->slug = '';
                $translation->meta_title = null;
                $translation->meta_description = null;
            }
            $tour->save();

            $tour->categories()->sync($source->categories->modelKeys());
            $tour->transportations()->sync($source->transportations->modelKeys());
            $tour->filterOptions()->sync($source->filterOptions->modelKeys());
            $tour->inclusions()->sync($source->inclusions->modelKeys());
            $tour->pickupPoints()->sync($source->pickupPoints->mapWithKeys(fn ($p) => [$p->id => ['sort_order' => $p->pivot->sort_order]])->all());

            if ($source->cruiseDetail) {
                $tour->cruiseDetail()->save($source->cruiseDetail->replicate(['tour_id']));
            }

            if ($options['itinerary'] ?? true) {
                foreach ($source->itineraryDays as $day) {
                    $newDay = $day->replicateWithTranslations(['tour_id']);
                    $newDay->tour_id = $tour->id;
                    $newDay->save();
                    foreach ($day->items as $item) {
                        $newItem = $item->replicateWithTranslations(['tour_itinerary_day_id']);
                        $newItem->tour_itinerary_day_id = $newDay->id;
                        $newItem->save();
                    }
                }
            }

            if ($options['content'] ?? true) {
                foreach ($source->contentItems as $content) {
                    $newContent = $content->replicateWithTranslations(['tour_id']);
                    $newContent->tour_id = $tour->id;
                    $newContent->save();
                }
            }

            if ($options['dates'] ?? false) {
                foreach ($source->departures as $departure) {
                    $newDeparture = $departure->replicate(['tour_id', 'reserved_seats', 'held_seats']);
                    $newDeparture->tour_id = $tour->id;
                    $newDeparture->reserved_seats = 0;
                    $newDeparture->held_seats = 0;
                    $newDeparture->save();
                    foreach ($departure->prices as $price) {
                        $newDeparture->prices()->save($price->replicate(['tour_departure_id']));
                    }
                    $newDeparture->pickupPoints()->sync($departure->pickupPoints->modelKeys());
                }
            }

            Activity::record('duplicated', $tour, ['copied_from' => $source->name.' (#'.$source->id.')', 'copied' => collect(['itinerary' => $options['itinerary'] ?? true, 'additional information' => $options['content'] ?? true, 'future dates' => $options['dates'] ?? false])->filter()->keys()->implode(', ') ?: 'tour details only']);

            return $tour;
        });
    }
}
