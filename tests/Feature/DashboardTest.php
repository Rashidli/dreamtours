<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Support\BookingMoney;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** CRM §2 — dashboard widgets; every number opens exactly the records behind it, and only for those allowed. */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private TourDeparture $soon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsPermissionSeeder::class);
        $this->admin = User::factory()->create(['name' => 'Jenn Manager']);
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']));
        $this->travelTo(now('America/Toronto')->setTime(10, 0)->utc());

        $tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Niagara Falls', 'slug' => 'niagara-'.uniqid()]]);
        $this->soon = TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->addDays(3)->setTime(12, 0), 'price' => 100, 'currency' => 'CAD', 'capacity' => 10, 'reserved_seats' => 0, 'status' => 'available']);
        $later = TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->addDays(10)->setTime(12, 0), 'price' => 100, 'currency' => 'CAD', 'capacity' => 20, 'reserved_seats' => 0, 'status' => 'available']);
        TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->addDays(40), 'price' => 100, 'currency' => 'CAD', 'capacity' => 20, 'reserved_seats' => 0, 'status' => 'available']);

        // Soon: 3 passengers on 2 bookings (one booked yesterday, overdue and unpaid; one today, paid in full).
        $old = $this->booking($this->soon, 'Olga', 2, '200.00', now()->subDay());
        $old->forceFill(['balance_due_date' => now()->subDay()->toDateString()])->saveQuietly();
        BookingMoney::sync($old->fresh());
        $new = $this->booking($this->soon, 'Nina', 1, '100.00', now());
        BookingMoney::record($new, ['amount' => '100.00', 'method' => 'cash', 'paid_on' => now()->toDateString()], $this->admin, true);
        BookingMoney::record($old, ['amount' => '40.00', 'method' => 'etransfer'], $this->admin, false); // pending
        $this->booking($later, 'Leo', 1, '100.00', now()->subDays(5));
        $cancelled = $this->booking($this->soon, 'Carl', 4, '400.00', now());
        $cancelled->forceFill(['status' => 'cancelled'])->saveQuietly();
        $this->soon->recountSeats(); // as the booking screen does when cancelling

        $newStage = LeadStage::where('kind', 'open')->orderBy('sort_order')->first();
        Lead::create(['lead_stage_id' => $newStage->id, 'first_name' => 'Fresh', 'last_name' => 'Lead', 'email' => 'fresh@example.com', 'source' => 'website', 'type' => 'tour']);
        Lead::create(['lead_stage_id' => LeadStage::where('kind', 'open')->orderBy('sort_order')->skip(1)->first()->id, 'first_name' => 'Call', 'last_name' => 'Back', 'email' => 'call@example.com', 'source' => 'phone', 'type' => 'tour', 'next_follow_up_at' => now()->subHour()]);
        ActivityLog::create(['actor_type' => 'system', 'actor_name' => 'System', 'action' => 'email_failed', 'subject_label' => 'Booking', 'changes' => [], 'origin' => 'queue', 'created_at' => now()->subDays(2)]);
        ActivityLog::create(['actor_type' => 'system', 'actor_name' => 'System', 'action' => 'email_failed', 'subject_label' => 'Booking', 'changes' => [], 'origin' => 'queue', 'created_at' => now()->subDays(20)]);
    }

    private function booking(TourDeparture $d, string $first, int $pax, string $total, $at): Booking
    {
        $b = Booking::create(['tour_id' => $d->tour_id, 'tour_departure_id' => $d->id, 'type' => 'tour', 'status' => 'new', 'first_name' => $first, 'last_name' => 'Test', 'email' => strtolower($first).'@example.com', 'phone' => '416-555-0101', 'participant_count' => $pax, 'seat_count' => $pax, 'currency' => 'CAD', 'total_amount' => $total, 'deposit_amount' => '0.00']);
        foreach (range(1, $pax) as $i) {
            BookingPassenger::create(['booking_id' => $b->id, 'first_name' => $first.$i, 'last_name' => 'Test', 'category' => 'adult']);
        }
        $b->forceFill(['created_at' => $at])->saveQuietly();
        $d->recountSeats();

        return $b->fresh();
    }

    private function tiles($response): array
    {
        return collect($response->viewData('dash')['tiles'])->mapWithKeys(fn ($t) => [$t[0] => [$t[1], $t[5]]])->all();
    }

    public function test_each_figure_is_right_and_opens_exactly_those_records(): void
    {
        $page = $this->actingAs($this->admin)->get(route('home'))->assertOk()->assertSee('Good morning, Jenn');
        $t = $this->tiles($page);

        // Bookings today counts every booking received today, cancelled ones too, like the list it opens.
        $this->assertSame([1, 1, 2, 3, 1, 1, 1], [$t['New leads'][0], $t['Follow-ups due'][0], $t['Bookings today'][0], $t['Passengers this week'][0], $t['Waiting for verification'][0], $t['Overdue balances'][0], $t['Failed emails'][0]]);

        // Following each link shows the same records.
        $this->get($t['New leads'][1])->assertOk()->assertSee('Fresh Lead')->assertDontSee('Call Back');
        $this->get($t['Follow-ups due'][1])->assertOk()->assertSee('Call Back')->assertDontSee('Fresh Lead');
        $this->get($t['Bookings today'][1])->assertOk()->assertSee('Nina')->assertSee('Carl')->assertDontSee('Olga')->assertDontSee('Leo');
        $this->get($t['Waiting for verification'][1])->assertOk()->assertSee('CA$40');
        $this->get($t['Overdue balances'][1])->assertOk()->assertSee('Olga')->assertDontSee('Nina');
        $this->get($t['Failed emails'][1])->assertOk();

        // Trips in the next 14 days: occupancy from live passengers, cancelled bookings excluded; day 40 not listed.
        $trips = $page->viewData('dash')['trips'];
        $this->assertCount(2, $trips);
        $this->assertSame([3, 1], [$trips[0]->passengers_count, $trips[0]->unpaid_count]);
        $page->assertSeeInOrder(['Niagara Falls', '3</strong> / 10 taken', '7 left', 'No bus yet'], false);
        $this->assertSame('100.00', number_format((float) $page->viewData('dash')['payments']['recentTotal'], 2, '.', ''));
    }

    public function test_people_only_see_widgets_for_what_they_can_open(): void
    {
        $content = User::factory()->create(['name' => 'Cleo Content']);
        $content->assignRole('Content Manager');
        $page = $this->actingAs($content)->get(route('home'))->assertOk();
        $this->assertSame([], $page->viewData('dash')['tiles']);
        foreach (['Upcoming trips', 'Recent payments'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $page->getContent(), "Content Manager must not see “{$hidden}”");
        }
        foreach (['Website & content', '<strong>Tours</strong>', '<strong>Pages</strong>'] as $shown) {
            $this->assertStringContainsString($shown, $page->getContent(), "Content Manager should see {$shown}");
        }
        $this->assertStringNotContainsString('<strong>Team</strong>', $page->getContent());

        $accountant = User::factory()->create(['name' => 'Ada Accountant']);
        $accountant->assignRole('Accountant');
        $t = $this->tiles($this->actingAs($accountant)->get(route('home'))->assertOk()->assertSee('unpaid'));
        $this->assertArrayHasKey('Overdue balances', $t);
        $this->assertArrayNotHasKey('New leads', $t);
        $this->assertArrayNotHasKey('Failed emails', $t);

        $sales = User::factory()->create(['name' => 'Sam Sales']);
        $sales->assignRole('Sales Manager');
        $page = $this->actingAs($sales)->get(route('home'))->assertOk();
        $this->assertArrayHasKey('New leads', $this->tiles($page));
        $this->assertArrayHasKey('Waiting for verification', $this->tiles($page)); // Sales Manager has list-payments and finance by default
        $this->assertArrayNotHasKey('Failed emails', $this->tiles($page));
    }
}
