<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\TourDeparture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** DreamTour Website Developer Change Request (11 Nov 2026). */
class ChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    private function bookableTour(array $tour = []): array
    {
        $model = Tour::create($tour + ['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Niagara Day Trip', 'slug' => 'niagara-day-trip']]);
        $departure = TourDeparture::create(['tour_id' => $model->id, 'departs_at' => now()->addMonth(), 'price' => 105, 'currency' => 'CAD', 'capacity' => 50, 'reserved_seats' => 0, 'status' => 'available']);

        return [$model, $departure];
    }

    // 01 — the "Invoice Request" button is gone from the public booking panel.
    public function test_tour_page_has_no_invoice_request_button(): void
    {
        $this->bookableTour();

        $this->get('/tours/niagara-day-trip')
            ->assertOk()
            ->assertSee('Book Now')
            ->assertDontSee('Invoice Request')
            ->assertDontSee("show('invoice')", false);
    }

    // 02 — each fare row is labelled by its own code and the tour's age limits.
    public function test_departure_pricing_labels_infant_child_and_adult_rows(): void
    {
        [, $departure] = $this->bookableTour(['infant_max_age' => 1, 'child_max_age' => 11]);
        foreach ([['adult', 105], ['child', 95], ['infant', 0]] as $i => [$code, $price]) {
            $departure->prices()->create(['code' => $code, 'price' => $price, 'sort_order' => $i]);
        }

        $this->get('/tours/niagara-day-trip')
            ->assertOk()
            ->assertSeeInOrder(['Per <b class="text-ink-900">adult (12+)</b>', 'CA$105', 'Per <b class="text-ink-900">child (2–11)</b>', 'CA$95', 'Per <b class="text-ink-900">infant (0–1)</b>', 'CA$0'], false);
    }
}
