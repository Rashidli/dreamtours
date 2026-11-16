<?php

namespace Tests\Feature;

use App\Models\DepartureStaff;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** The /admin sign-in page: signed-in staff skip it; signing in starts a fresh session and returns to the page asked for. */
class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsPermissionSeeder::class);
    }

    public function test_signed_in_staff_are_sent_from_the_sign_in_page_to_their_start_page(): void
    {
        $this->get('/admin')->assertOk()->assertSee('password');

        $admin = User::factory()->create();
        $admin->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']));
        $this->actingAs($admin)->get('/admin')->assertRedirect(route('home'));
        $this->actingAs($admin)->post(route('login_submit'), ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('home'));

        // A tour leader's start page is My trips.
        $leader = User::factory()->create();
        $leader->assignRole('Tour Leader');
        $tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Niagara', 'slug' => 'n-'.uniqid()]]);
        $d = TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->addDays(3), 'price' => 100, 'currency' => 'CAD', 'capacity' => 10, 'reserved_seats' => 0, 'status' => 'available']);
        DepartureStaff::create(['tour_departure_id' => $d->id, 'user_id' => $leader->id, 'role' => 'leader']);
        $this->actingAs($leader)->get('/admin')->assertRedirect(route('home'));
        $this->actingAs($leader)->get(route('home'))->assertRedirect(route('my-trips.index'));
    }

    public function test_signing_in_regenerates_the_session_and_returns_to_the_requested_page(): void
    {
        $admin = User::factory()->create(['password' => bcrypt('secret-pass-1')]);
        $admin->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']));

        $this->get(route('payments.index'))->assertRedirect(route('login'));
        $before = session()->getId();
        $this->post(route('login_submit'), ['email' => $admin->email, 'password' => 'secret-pass-1'])->assertRedirect(route('payments.index'));
        $this->assertNotSame($before, session()->getId());
        $this->assertAuthenticatedAs($admin);

        auth()->logout();
        $this->post(route('login_submit'), ['email' => $admin->email, 'password' => 'wrong'])->assertSessionHasErrors('password');
        $this->assertGuest();
    }
}
