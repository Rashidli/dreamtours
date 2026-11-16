<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VehicleLayout;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** CRM §9 — reusable seating templates drawn on a grid. */
class VehicleLayoutTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsPermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']));
    }

    private function plan(array $rows): array
    {
        // Compact notation for tests: "D" driver, "L" leader, ">" door, "|" aisle, "." empty, anything else a seat label.
        $map = ['D' => 'driver', 'L' => 'leader', '>' => 'door', '|' => 'aisle', '.' => 'empty'];
        $cells = array_map(fn ($row) => array_map(fn ($c) => isset($map[$c]) ? ['t' => $map[$c]] : ['t' => 'seat', 'l' => $c], explode(' ', $row)), $rows);

        return ['rows' => count($cells), 'cols' => count($cells[0]), 'cells' => $cells];
    }

    public function test_standard_templates_exist_with_driver_door_and_numbered_seats(): void
    {
        $this->assertSame(['Coach (56 seats)' => 56, 'Minivan (7 seats)' => 7, 'Sprinter (16 seats)' => 16], VehicleLayout::orderBy('name')->pluck('seat_count', 'name')->all());
        $coach = VehicleLayout::where('name', 'Coach (56 seats)')->first()->layout;
        $this->assertSame(['driver', 'empty', 'aisle', 'leader', 'door'], array_column($coach['cells'][0], 't'));
        $this->assertSame(['1', '2', '3', '4'], array_slice(VehicleLayout::seatLabels($coach), 0, 4));
        $this->assertSame('56', VehicleLayout::seatLabels($coach)[55]);
        $this->actingAs($this->admin)->get(route('vehicle-layouts.index'))->assertOk()->assertSee('Coach (56 seats)')->assertSee('56 passenger seats');
    }

    public function test_a_drawn_plan_is_saved_and_its_seats_counted(): void
    {
        $plan = $this->plan(['D | L', '1A | 1B', '2A | 2B', '3 4 5']);
        $this->actingAs($this->admin)->get(route('vehicle-layouts.create'))->assertOk()->assertSee('Build this layout');
        $this->post(route('vehicle-layouts.store'), ['name' => 'Ford Transit', 'is_active' => 1, 'layout' => json_encode($plan)])->assertRedirect(route('vehicle-layouts.index'));
        $layout = VehicleLayout::where('name', 'Ford Transit')->firstOrFail();
        $this->assertSame(7, $layout->seat_count);
        $this->assertSame(['1A', '1B', '2A', '2B', '3', '4', '5'], VehicleLayout::seatLabels($layout->layout));

        $this->get(route('vehicle-layouts.edit', $layout))->assertOk()->assertSee('Ford Transit');
        $this->put(route('vehicle-layouts.update', $layout), ['name' => 'Ford Transit', 'layout' => json_encode($this->plan(['D | L', '1 | 2']))])->assertRedirect();
        $this->assertSame(2, $layout->fresh()->seat_count);
        $this->assertFalse($layout->fresh()->is_active);
    }

    public function test_plans_with_duplicate_or_bad_seat_numbers_are_refused(): void
    {
        $this->actingAs($this->admin);
        foreach ([
            'duplicate' => $this->plan(['D | L', '1 | 1']),
            'case-insensitive duplicate' => $this->plan(['D | L', '1a | 1A']),
            'no seats' => $this->plan(['D | L']),
            'ragged rows' => ['cells' => [[['t' => 'seat', 'l' => '1']], [['t' => 'seat', 'l' => '2'], ['t' => 'aisle']]]],
            'bad label' => $this->plan(['D | L', '1<b> | 2']),
            'bad type' => ['cells' => [[['t' => 'trampoline']]]],
        ] as $why => $plan) {
            $this->post(route('vehicle-layouts.store'), ['name' => "Bad $why", 'layout' => json_encode($plan)])->assertSessionHasErrors('layout');
        }
        $this->assertSame(0, VehicleLayout::where('name', 'like', 'Bad %')->count());
    }

    public function test_templates_need_their_permissions_and_removing_one_only_archives_it(): void
    {
        $accountant = User::factory()->create();
        $accountant->assignRole('Accountant');
        $this->actingAs($accountant)->get(route('vehicle-layouts.index'))->assertForbidden();
        $this->post(route('vehicle-layouts.store'), ['name' => 'X', 'layout' => json_encode($this->plan(['1']))])->assertForbidden();

        $van = VehicleLayout::where('name', 'Minivan (7 seats)')->firstOrFail();
        $this->actingAs($this->admin)->delete(route('vehicle-layouts.destroy', $van))->assertRedirect();
        $this->assertSoftDeleted($van);
    }
}
