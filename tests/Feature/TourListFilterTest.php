<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Tour;
use App\Models\TourCategory;
use App\Models\TourDeparture;
use App\Models\User;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Tours list: Excel-style sorting and filters on every column, including dates. */
class TourListFilterTest extends TestCase
{
    use RefreshDatabase;

    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsPermissionSeeder::class);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $this->actingAs(tap(User::factory()->create())->assignRole('superadmin'));

        $canada = Destination::create(['type' => 'country', 'is_active' => 1, 'en' => ['name' => 'Canada']]);
        $usa = Destination::create(['type' => 'country', 'is_active' => 1, 'en' => ['name' => 'USA']]);
        $oneDay = TourCategory::create(['type' => 'tour', 'is_active' => 1, 'en' => ['name' => 'One Day']]);
        $multi = TourCategory::create(['type' => 'tour', 'is_active' => 1, 'en' => ['name' => 'Multi Day']]);

        $rows = [
            // name, destination, category, status, dates (days from now)
            ['Algonquin Park', $canada, $oneDay, 'published', [10, 40]],
            ['Boston Weekend', $usa, $multi, 'draft', [70]],
            ['Cape Cod Trip', null, null, 'archived', [-30]],
            ['Dream Private Tour', $canada, $multi, 'published', []],
        ];
        foreach ($rows as $i => [$name, $destination, $category, $status, $dates]) {
            $tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => $status, 'sort_order' => $i, 'destination_id' => $destination?->id, 'en' => ['name' => $name]]);
            if ($category) {
                $tour->categories()->sync([$category->id]);
            }
            foreach ($dates as $days) {
                TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->addDays($days)->setTime(8, 0), 'price' => 100, 'currency' => 'CAD', 'capacity' => 50, 'reserved_seats' => 0, 'status' => 'available']);
            }
            $this->ids[$name] = $tour->id;
        }
        $this->ids['canada'] = $canada->id;
        $this->ids['usa'] = $usa->id;
        $this->ids['multi'] = $multi->id;
    }

    private function list(array $query)
    {
        return $this->get(route('tours.index', $query))->assertOk();
    }

    public function test_several_values_per_column_like_excel(): void
    {
        $this->list(['status' => ['draft', 'archived']])->assertSee('Boston Weekend')->assertSee('Cape Cod Trip')->assertDontSee('Algonquin Park');
        $this->list(['destination_id' => [$this->ids['usa'], 0]])->assertSee('Boston Weekend')->assertSee('Cape Cod Trip')->assertDontSee('Algonquin Park')->assertDontSee('Dream Private Tour');
        $this->list(['category_id' => [$this->ids['multi']]])->assertSee('Boston Weekend')->assertSee('Dream Private Tour')->assertDontSee('Algonquin Park');
        $this->list(['category_id' => [0]])->assertSee('Cape Cod Trip')->assertDontSee('Boston Weekend');
        // Older single-value links keep working.
        $this->list(['status' => 'published'])->assertSee('Algonquin Park')->assertSee('Dream Private Tour')->assertDontSee('Boston Weekend');
    }

    public function test_date_range_and_no_dates(): void
    {
        $this->list(['date_from' => now()->addDays(30)->format('Y-m-d'), 'date_to' => now()->addDays(80)->format('Y-m-d')])
            ->assertSee('Algonquin Park')->assertSee('Boston Weekend')->assertDontSee('Cape Cod Trip')->assertDontSee('Dream Private Tour')
            ->assertSee(now()->addDays(40)->format('M j, Y'))->assertDontSee(now()->addDays(10)->format('M j, Y'));
        $this->list(['when' => 'none'])->assertSee('Dream Private Tour')->assertDontSee('Algonquin Park');
        $this->list(['when' => 'past'])->assertSee('Cape Cod Trip')->assertDontSee('Boston Weekend');
        $this->get(route('tours.index', ['date_from' => '2026-05-10', 'date_to' => '2026-05-01']))->assertSessionHasErrors('date_to');
    }

    public function test_every_column_sorts_both_ways(): void
    {
        $this->list(['sort' => 'name', 'dir' => 'desc'])->assertSeeInOrder(['Dream Private Tour', 'Cape Cod Trip', 'Boston Weekend', 'Algonquin Park']);
        // Empty values (no destination / category / dates) go last in both directions.
        $this->list(['sort' => 'destination', 'dir' => 'asc'])->assertSeeInOrder(['Algonquin Park', 'Dream Private Tour', 'Boston Weekend', 'Cape Cod Trip']);
        $this->list(['sort' => 'destination', 'dir' => 'desc'])->assertSeeInOrder(['Boston Weekend', 'Algonquin Park', 'Cape Cod Trip']);
        $this->list(['sort' => 'category', 'dir' => 'asc'])->assertSeeInOrder(['Boston Weekend', 'Algonquin Park', 'Cape Cod Trip']);
        $this->list(['sort' => 'dates', 'dir' => 'asc'])->assertSeeInOrder(['Cape Cod Trip', 'Algonquin Park', 'Boston Weekend', 'Dream Private Tour']);
        $this->list(['sort' => 'dates', 'dir' => 'desc'])->assertSeeInOrder(['Boston Weekend', 'Algonquin Park', 'Cape Cod Trip', 'Dream Private Tour']);
        $this->list(['sort' => 'status', 'dir' => 'asc'])->assertSeeInOrder(['Cape Cod Trip', 'Boston Weekend', 'Algonquin Park']);
    }

    public function test_active_filters_show_as_removable_chips(): void
    {
        $this->list(['status' => ['draft'], 'q' => 'Bos'])
            ->assertSee('Status: Draft')->assertSee('Search: “Bos”', false)->assertSee('Clear all filters')
            ->assertSee('class="tl-filter-btn active"', false);
    }
}
