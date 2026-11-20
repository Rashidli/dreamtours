<?php

namespace Tests\Feature;

use App\Models\StaffAlert;
use App\Models\User;
use Database\Seeders\CmsPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** "Mark all done" closes exactly what the alerts list shows: the same kind and "assigned to me" filters. */
class AlertsCompleteAllTest extends TestCase
{
    use RefreshDatabase;

    private User $me;
    private User $colleague;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CmsPermissionSeeder::class);
        Cache::put('staff-alerts-sweep', 1, 600); // no time-based alerts during these tests
        $role = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        $this->me = User::factory()->create(['name' => 'Jenn Manager']);
        $this->me->assignRole($role);
        $this->colleague = User::factory()->create(['name' => 'Maria Lopez']);
        $this->colleague->assignRole($role);
    }

    private function alert(string $type, ?User $assigned = null): StaffAlert
    {
        $a = new StaffAlert();
        $a->forceFill([
            'type' => $type, 'dedupe_key' => $type.':'.uniqid('', true), 'title' => 'Test '.$type, 'url' => null,
            'permission' => \App\Support\Alerts::TYPES[$type][3], 'assigned_user_id' => $assigned?->id,
        ])->save();

        return $a;
    }

    public function test_mark_all_with_mine_leaves_other_peoples_alerts_open(): void
    {
        $mine = $this->alert('new_inquiry', $this->me);
        $theirs = $this->alert('new_inquiry', $this->colleague);
        $nobody = $this->alert('new_inquiry');

        $this->actingAs($this->me)->from(route('alerts.index', ['mine' => 1]))
            ->post(route('alerts.complete-all'), ['type' => '', 'mine' => 1])
            ->assertRedirect(route('alerts.index', ['mine' => 1]))->assertSessionHas('message', '1 alert marked done.');

        $this->assertNotNull($mine->fresh()->completed_at);
        $this->assertSame($this->me->id, $mine->fresh()->completed_by);
        $this->assertNull($theirs->fresh()->completed_at);
        $this->assertNull($nobody->fresh()->completed_at);
    }

    public function test_mark_all_with_a_type_only_closes_that_type(): void
    {
        $inquiries = [$this->alert('new_inquiry'), $this->alert('new_inquiry', $this->colleague)];
        $payment = $this->alert('payment_pending');
        $full = $this->alert('tour_full');

        $this->actingAs($this->me)->post(route('alerts.complete-all'), ['type' => 'new_inquiry'])
            ->assertRedirect()->assertSessionHas('message', '2 alerts marked done.');

        foreach ($inquiries as $a) {
            $this->assertNotNull($a->fresh()->completed_at);
        }
        $this->assertNull($payment->fresh()->completed_at);
        $this->assertNull($full->fresh()->completed_at);
    }

    public function test_the_list_sends_its_filters_with_mark_all_done(): void
    {
        $this->alert('new_inquiry', $this->me);
        $this->alert('new_inquiry', $this->colleague);

        $this->actingAs($this->me)->get(route('alerts.index', ['type' => 'new_inquiry', 'mine' => 1]))->assertOk()
            ->assertSee('<input type="hidden" name="type" value="new_inquiry">', false)
            ->assertSee('<input type="hidden" name="mine" value="1">', false)
            ->assertSee('Mark the 1 open', false);
    }

    public function test_an_unknown_type_is_rejected(): void
    {
        $a = $this->alert('new_inquiry');
        $this->actingAs($this->me)->post(route('alerts.complete-all'), ['type' => 'nope'])->assertSessionHasErrors('type');
        $this->assertNull($a->fresh()->completed_at);
    }
}
