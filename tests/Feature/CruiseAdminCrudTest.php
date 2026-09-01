<?php

namespace Tests\Feature;

use App\Models\Tour;
use App\Models\User;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CruiseAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_cruise_details_are_created_updated_and_removed_with_tour(): void
    {
        $this->seed(CmsPermissionSeeder::class);
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('superadmin');
        $this->actingAs($user);

        $payload = [
            'duration_days' => 8,
            'duration_nights' => 7,
            'status' => 'published',
            'is_cruise' => 1,
            'en' => ['name' => 'Ocean Cruise', 'slug' => 'ocean-cruise'],
            'cruise' => [
                'company' => 'Ocean Line',
                'departure_port' => 'Miami',
                'cabin_type' => 'Balcony, Suite',
                'documents' => "Passport\nInsurance",
            ],
        ];

        $this->post(route('tours.store'), $payload)->assertRedirect();
        $tour = Tour::with('cruiseDetail')->firstOrFail();
        $this->assertSame('Ocean Line', $tour->cruiseDetail->company);
        $this->assertSame(['Passport', 'Insurance'], $tour->cruiseDetail->documents);
        $this->get(route('tours.edit', $tour))->assertOk()->assertSee('Ocean Line');

        $payload['cruise']['company'] = 'Updated Line';
        $this->put(route('tours.update', $tour), $payload)->assertRedirect();
        $this->assertDatabaseHas('cruise_details', ['tour_id' => $tour->id, 'company' => 'Updated Line']);

        unset($payload['is_cruise']);
        $this->put(route('tours.update', $tour), $payload)->assertRedirect();
        $this->assertDatabaseMissing('cruise_details', ['tour_id' => $tour->id]);
    }
}
