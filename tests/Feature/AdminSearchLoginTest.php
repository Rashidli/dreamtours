<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tour;
use App\Models\User;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Admin top bar search and the sign-in page. */
class AdminSearchLoginTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(CmsPermissionSeeder::class);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);

        return tap(User::factory()->create(['name' => 'Jenn Smith']))->assignRole('superadmin');
    }

    public function test_search_finds_tours_and_customers_by_name_email_or_phone(): void
    {
        $this->actingAs($this->admin());
        Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Niagara Falls Day Trip']]);
        Customer::create(['first_name' => 'Maria', 'last_name' => 'Lopez', 'email' => 'maria@example.com', 'phone' => '416-555-0101', 'phone_digits' => '4165550101']);

        $this->get(route('admin.search', ['q' => 'niagara']))->assertOk()->assertSee('Niagara Falls Day Trip')->assertDontSee('Maria Lopez');
        $this->get(route('admin.search', ['q' => 'maria@example']))->assertOk()->assertSee('Maria Lopez');
        $this->get(route('admin.search', ['q' => '555 0101']))->assertOk()->assertSee('Maria Lopez');
        $this->get(route('admin.search', ['q' => 'zzzz']))->assertOk()->assertSee('Nothing found');
        $this->get(route('admin.search', ['q' => 'a']))->assertOk()->assertSee('at least 2 characters');
    }

    public function test_search_shows_only_what_the_user_may_list(): void
    {
        $this->seed(CmsPermissionSeeder::class);
        $this->actingAs(User::factory()->create());
        Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'en' => ['name' => 'Secret Niagara']]);

        $this->get(route('admin.search', ['q' => 'niagara']))->assertOk()->assertDontSee('Secret Niagara');
    }

    public function test_top_bar_has_the_search_and_account_menu(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('home'))->assertOk()
            ->assertSee('action="'.route('admin.search').'"', false)
            ->assertSee('JS</span>', false)
            ->assertDontSee('data-toggle="fullscreen"', false);
    }

    public function test_login_page_and_sign_in_with_remember_me(): void
    {
        $user = User::factory()->create(['password' => Hash::make('right-password')]);

        $this->get(route('login'))->assertOk()->assertSee('Log in')->assertSee('name="remember"', false);
        $this->post(route('login_submit'), ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('password');
        $this->post(route('login_submit'), ['email' => $user->email, 'password' => 'right-password', 'remember' => 1])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create(['password' => Hash::make('right-password')]);
        foreach (range(1, 6) as $i) {
            $this->post(route('login_submit'), ['email' => $user->email, 'password' => 'wrong-'.$i]);
        }
        $this->post(route('login_submit'), ['email' => $user->email, 'password' => 'right-password'])->assertStatus(429);
    }
}
