<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Models\VisaEnquiry;
use App\Support\Leads;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** CRM requirements §3 — website requests and the lead pipeline. */
class LeadTest extends TestCase
{
    use RefreshDatabase;

    private function departure(string $name = 'Niagara Halloween Tour'): TourDeparture
    {
        $tour = Tour::create(['duration_days' => 1, 'duration_nights' => 0, 'status' => 'published', 'deposit_per_person' => 50, 'en' => ['name' => $name, 'slug' => str($name)->slug()]]);

        return TourDeparture::create(['tour_id' => $tour->id, 'departs_at' => now()->addMonth()->setTime(8, 0), 'price' => 120, 'currency' => 'CAD', 'capacity' => 20, 'reserved_seats' => 0, 'status' => 'available']);
    }

    private function request(array $fields): Lead
    {
        $this->post(route('front.bookings.store'), $fields + ['first_name' => 'Maria', 'last_name' => 'Lopez', 'phone' => '416-555-0101', 'email' => 'maria@example.com', 'participant_count' => 2])->assertRedirect();

        return Lead::latest('id')->firstOrFail();
    }

    private function staff(?array $permissions = null, string $name = 'Jenn Manager'): User
    {
        $this->seed(CmsPermissionSeeder::class);
        $user = User::factory()->create(['name' => $name]);
        $permissions === null ? $user->assignRole(Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web'])) : $user->givePermissionTo($permissions);
        $this->actingAs($user);

        return $user;
    }

    // AT-01 + AT-15 — a request from a campaign landing page becomes a lead with its source, trip, time and UTM labels.
    public function test_website_request_becomes_a_lead_with_campaign_and_trip(): void
    {
        $departure = $this->departure();
        $this->get('/tours/niagara-halloween-tour?utm_source=facebook&utm_medium=cpc&utm_campaign=halloween-2026&utm_content=video-a', ['Referer' => 'https://m.facebook.com/'])->assertOk();
        $this->get('/contact')->assertOk(); // browsing on keeps the first touch

        $lead = $this->request(['type' => 'tour', 'tour_id' => $departure->tour_id, 'tour_departure_id' => $departure->id, 'comments' => 'Can we bring a stroller?']);

        $this->assertSame(['new', 'booking', 'website', 'facebook', 'cpc', 'halloween-2026', 'video-a'], [$lead->stage->key, $lead->type, $lead->source, $lead->utm_source, $lead->utm_medium, $lead->utm_campaign, $lead->utm_content]);
        $this->assertStringContainsString('/tours/niagara-halloween-tour?', $lead->landing_page);
        $this->assertStringContainsString('utm_campaign=halloween-2026', $lead->landing_page);
        $this->assertSame('https://m.facebook.com/', $lead->referrer);
        $this->assertSame([$departure->tour_id, $departure->id, 2, 'Can we bring a stroller?'], [$lead->tour_id, $lead->tour_departure_id, $lead->guests, $lead->message]);
        $this->assertSame($lead->booking_id, $lead->request_booking_id);
        $this->assertSame(Customer::where('email', 'maria@example.com')->value('id'), $lead->customer_id);
        $this->assertSame('Website · facebook / cpc · halloween-2026', $lead->sourceLabel());
    }

    public function test_enquiries_and_visa_requests_become_leads_too(): void
    {
        $this->get('/?gclid=abc123')->assertOk();
        $callback = $this->request(['type' => 'callback', 'comments' => 'Call after 6pm']);
        $this->assertSame(['callback', null, 'google', 'cpc'], [$callback->type, $callback->booking_id, $callback->utm_source, $callback->utm_medium]);
        $this->assertNotNull($callback->request_booking_id);

        $enquiry = VisaEnquiry::create(['full_name' => 'Ana Silva', 'email' => 'ana@example.com', 'phone' => '+14165550123', 'country' => 'USA', 'assistance' => ['visa'], 'applicants' => 2]);
        $visa = Leads::fromVisaEnquiry($enquiry);
        $this->assertSame(['visa', 'Ana', 'Silva', 2, $enquiry->customer_id], [$visa->type, $visa->first_name, $visa->last_name, $visa->guests, $visa->customer_id]);
    }

    public function test_assigning_and_logging_contact_move_the_lead_along(): void
    {
        $lead = $this->request(['type' => 'contact']);
        $sam = User::factory()->create(['name' => 'Sam Sales']);
        $this->staff();

        $this->patch(route('leads.assign', $lead), ['assigned_user_id' => $sam->id])->assertRedirect();
        $this->assertSame(['assigned', $sam->id], [$lead->fresh()->stage->key, $lead->fresh()->assigned_user_id]);

        $this->post(route('leads.note', $lead), ['kind' => 'note', 'body' => 'Asked about group discount'])->assertRedirect();
        $this->assertSame([0, 'assigned'], [$lead->fresh()->contact_attempts, $lead->fresh()->stage->key]);

        $this->post(route('leads.note', $lead), ['kind' => 'call', 'outcome' => 'no_answer'])->assertRedirect();
        $this->post(route('leads.note', $lead), ['kind' => 'whatsapp', 'outcome' => 'reached', 'body' => 'Sent prices'])->assertRedirect();
        $lead->refresh();
        $this->assertSame([2, 'contacted'], [$lead->contact_attempts, $lead->stage->key]);
        $this->assertNotNull($lead->last_contacted_at);
        $this->assertSame(3, $lead->notes()->count());
        $this->assertSame(['Assigned', 'Contacted'], ActivityLog::where(['subject_id' => $lead->id, 'action' => 'stage_changed'])->orderBy('id')->get()->map(fn ($l) => $l->changes['stage'][1])->all());

        $this->get(route('leads.show', $lead))->assertOk()->assertSee('Sent prices')->assertSee('No answer')->assertSee('2 contact attempts');
    }

    public function test_lost_needs_a_reason_and_board_moves_answer_json(): void
    {
        $lead = $this->request(['type' => 'contact']);
        $this->staff();
        $lost = LeadStage::byKey('lost');

        $this->patch(route('leads.stage', $lead), ['lead_stage_id' => $lost->id])->assertSessionHasErrors('lost_reason');
        $this->patchJson(route('leads.stage', $lead), ['lead_stage_id' => $lost->id])->assertStatus(422)->assertJson(['needs_reason' => true]);
        $this->patchJson(route('leads.stage', $lead), ['lead_stage_id' => $lost->id, 'lost_reason' => 'Price too high'])->assertOk()->assertJson(['stage' => 'Lost']);
        $this->assertSame(['lost', 'Price too high'], [$lead->fresh()->stage->key, $lead->fresh()->lost_reason]);

        $this->patchJson(route('leads.stage', $lead), ['lead_stage_id' => LeadStage::byKey('contacted')->id])->assertOk();
        $this->assertNull($lead->fresh()->lost_reason, 'reopening clears the lost reason');
    }

    public function test_convert_to_booking_copies_everything_and_confirming_closes_the_lead(): void
    {
        $departure = $this->departure();
        $lead = $this->request(['type' => 'private_tour', 'comments' => 'Family of four']);
        $this->staff();

        $this->post(route('leads.convert', $lead), ['tour_departure_id' => $departure->id, 'guests' => 25])->assertSessionHasErrors('guests'); // only 20 seats
        $response = $this->post(route('leads.convert', $lead), ['tour_departure_id' => $departure->id, 'guests' => 4]);
        $booking = Booking::where('source', 'lead')->firstOrFail();
        $response->assertRedirect(route('bookings.show', $booking));

        $this->assertSame(['Maria', 'maria@example.com', '416-555-0101', 4, 'Family of four', 'payment_pending'], [$booking->first_name, $booking->email, $booking->phone, $booking->participant_count, $booking->comments, $booking->status]);
        $this->assertEquals([480, 200], [$booking->total_amount, $booking->deposit_amount]);
        $this->assertSame($lead->customer_id, $booking->customer_id);
        $lead->refresh();
        $this->assertSame([$booking->id, 'awaiting_deposit'], [$lead->booking_id, $lead->stage->key]);
        $this->post(route('leads.convert', $lead), ['tour_departure_id' => $departure->id, 'guests' => 1])->assertStatus(409);
        $this->get(route('bookings.show', $booking))->assertOk()->assertSee($lead->reference);

        $this->put(route('bookings.update', $booking), ['status' => 'confirmed', 'payment_status' => 'deposit_paid', 'total_amount' => 480, 'amount_paid' => 200])->assertRedirect();
        $this->assertSame('confirmed', $lead->fresh()->stage->key);
    }

    public function test_follow_up_reminders_and_due_filter(): void
    {
        $lead = $this->request(['type' => 'contact']);
        $this->staff();

        $this->patch(route('leads.follow-up', $lead), ['when' => 'tomorrow', 'follow_up_note' => 'Send the brochure'])->assertRedirect();
        $this->assertTrue($lead->fresh()->next_follow_up_at->isFuture());
        $this->assertFalse($lead->fresh()->isFollowUpDue());

        $lead->forceFill(['next_follow_up_at' => now()->subHour()])->save();
        $this->assertSame(1, Lead::followUpDue()->count());
        $this->get(route('leads.index', ['view' => 'list', 'due' => 1]))->assertOk()->assertSee('Maria Lopez')->assertSee('1 follow-up due');
        $this->get(route('home'))->assertSee('title="New leads and follow-ups due">2<', false); // 1 new + 1 due

        $this->patch(route('leads.follow-up', $lead), ['when' => 'clear'])->assertRedirect();
        $this->assertNull($lead->fresh()->next_follow_up_at);
    }

    public function test_manual_leads_link_to_customers(): void
    {
        $existing = Customer::create(['first_name' => 'Olga', 'email' => 'olga@example.com']);
        $me = $this->staff();

        $this->post(route('leads.store'), ['first_name' => 'Nobody', 'source' => 'phone'])->assertSessionHasErrors('email');
        $this->post(route('leads.store'), ['first_name' => 'Olga', 'email' => 'OLGA@example.com', 'source' => 'walk_in', 'guests' => 3, 'message' => 'Wants Ottawa in May', 'assigned_user_id' => $me->id])->assertRedirect();

        $lead = Lead::where('type', 'manual')->firstOrFail();
        $this->assertSame([$existing->id, 'walk_in', 'assigned', $me->id], [$lead->customer_id, $lead->source, $lead->stage->key, $lead->assigned_user_id]);
    }

    public function test_board_and_list_filters(): void
    {
        $departure = $this->departure();
        $this->get('/?utm_source=newsletter&utm_campaign=spring-promo')->assertOk();
        $promo = $this->request(['type' => 'tour', 'tour_id' => $departure->tour_id, 'tour_departure_id' => $departure->id]);
        $this->flushSession();
        $other = $this->request(['type' => 'callback', 'first_name' => 'Sam', 'last_name' => 'Lee', 'email' => 'sam@example.com', 'phone' => '647-555-0000']);
        $me = $this->staff();
        Leads::assign($other, $me);

        $this->get(route('leads.index'))->assertOk()->assertSee('Maria Lopez')->assertSee('Sam Lee')->assertSee('spring-promo')->assertSee('Drag a card');
        $list = fn (array $q) => $this->get(route('leads.index', $q + ['view' => 'list']))->assertOk();
        $list(['assigned' => 'me'])->assertSee('Sam Lee')->assertDontSee('Maria Lopez');
        $list(['assigned' => 'none'])->assertSee('Maria Lopez')->assertDontSee('Sam Lee');
        $list(['campaign' => 'spring-promo'])->assertSee('Maria Lopez')->assertDontSee('Sam Lee');
        $list(['q' => '647 555'])->assertSee('Sam Lee')->assertDontSee('Maria Lopez');
        $list(['q' => $promo->reference])->assertSee('Maria Lopez')->assertDontSee('Sam Lee');
        $list(['type' => 'callback'])->assertSee('Sam Lee')->assertDontSee('Maria Lopez');
        $list(['tour' => $departure->tour_id])->assertSee('Maria Lopez')->assertDontSee('Sam Lee');
        $list(['stage' => LeadStage::byKey('assigned')->id])->assertSee('Sam Lee')->assertDontSee('Maria Lopez');
    }

    public function test_stages_are_editable_but_built_in_ones_stay(): void
    {
        $this->staff();
        $contacted = LeadStage::byKey('contacted');

        $this->post(route('lead-stages.store'), ['name' => 'Quote sent', 'color' => 'info', 'kind' => 'open'])->assertRedirect();
        $quote = LeadStage::where('name', 'Quote sent')->firstOrFail();
        $this->assertSame($contacted->sort_order + 1, $quote->sort_order, 'new open stages go before the closed ones');

        $this->put(route('lead-stages.update', $contacted), ['name' => 'Called', 'color' => 'warning', 'kind' => 'lost'])->assertRedirect();
        $this->assertSame(['Called', 'open', 'contacted'], [$contacted->fresh()->name, $contacted->fresh()->kind, $contacted->fresh()->key]);
        $this->post(route('lead-stages.archive', $contacted))->assertStatus(422);
        $this->delete(route('lead-stages.destroy', $contacted))->assertStatus(422);

        $this->post(route('lead-stages.move', $quote), ['direction' => 'up'])->assertRedirect();
        $this->assertTrue($quote->fresh()->sort_order < $contacted->fresh()->sort_order);
        $this->delete(route('lead-stages.destroy', $quote))->assertRedirect();
        $this->assertModelMissing($quote);
    }

    public function test_lead_pages_need_permissions(): void
    {
        $lead = $this->request(['type' => 'contact']);
        $this->staff(['list-customers']);
        $this->get(route('leads.index'))->assertForbidden();
        $this->get(route('leads.show', $lead))->assertForbidden();

        $this->staff(['list-leads'], 'Viewer');
        $this->get(route('leads.show', $lead))->assertOk()->assertDontSee('Convert to booking')->assertDontSee('Log a contact');
        $this->patch(route('leads.assign', $lead), ['assigned_user_id' => null])->assertForbidden();
        $this->get(route('lead-stages.index'))->assertForbidden();
    }

    public function test_backfill_command_creates_leads_in_the_right_stage_once(): void
    {
        $departure = $this->departure();
        $booking = Booking::create(['tour_id' => $departure->tour_id, 'tour_departure_id' => $departure->id, 'type' => 'tour', 'status' => 'confirmed', 'first_name' => 'Old', 'last_name' => 'Booking', 'email' => 'old@example.com', 'phone' => '416-555-0777', 'participant_count' => 1]);
        $booking->forceFill(['created_at' => now()->subMonths(2)])->saveQuietly();

        $this->artisan('crm:create-leads --dry-run')->assertSuccessful();
        $this->assertSame(0, Lead::count());
        $this->artisan('crm:create-leads')->expectsOutputToContain('1 leads created')->assertSuccessful();
        $this->artisan('crm:create-leads')->expectsOutputToContain('0 leads created')->assertSuccessful();
        $lead = Lead::firstOrFail();
        $this->assertSame(['confirmed', $booking->id], [$lead->stage->key, $lead->booking_id]);
        $this->assertTrue($lead->created_at->lt(now()->subMonth()));
    }
}
