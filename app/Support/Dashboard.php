<?php

namespace App\Support;

use App\Http\Controllers\Admin\ActivityLogController;
use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\BookingPayment;
use App\Models\DepartureVehicle;
use App\Models\Lead;
use App\Models\LeadStage;
use App\Models\TourDeparture;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The CRM dashboard (CRM §2). Each figure is computed only when the person may open what is
 * behind it, and comes with the link to exactly those records.
 */
class Dashboard
{
    public const TRIP_DAYS = 14;
    public const RECENT_PAYMENT_DAYS = 7;
    public const FAILED_EMAIL_DAYS = 14;

    public static function for(User $user): array
    {
        $tz = ActivityLogController::DISPLAY_TIMEZONE;
        $today = now($tz)->toDateString();
        $tiles = [];
        $out = ['trips' => null, 'followUps' => null, 'payments' => null];

        if ($user->can('list-leads')) {
            $new = LeadStage::where('kind', 'open')->orderBy('sort_order')->first();
            $tiles[] = ['New leads', $new ? Lead::where('lead_stage_id', $new->id)->count() : 0, 'waiting for first contact', 'primary', 'ri-user-star-line', route('leads.index', ['view' => 'list', 'stage' => $new?->id])];
            $due = Lead::followUpDue()->count();
            $tiles[] = ['Follow-ups due', $due, 'calls and messages to make', $due ? 'danger' : 'secondary', 'ri-alarm-line', route('leads.index', ['view' => 'list', 'due' => 1])];
            $out['followUps'] = Lead::followUpDue()->with(['assignedUser', 'tour.translations'])->orderBy('next_follow_up_at')->limit(5)->get();
        }

        if ($user->can('list-bookings')) {
            $tiles[] = ['Bookings today', Booking::where('created_at', '>=', Carbon::parse($today, $tz)->startOfDay()->utc())->count(), 'new bookings and requests', 'success', 'ri-calendar-check-line', route('bookings.index', ['booked_from' => $today, 'booked_to' => $today])];
            $out['trips'] = self::trips();
            $soon = $out['trips']->filter(fn ($d) => $d->departs_at->lt(now()->addDays(7)));
            $tiles[] = ['Passengers this week', $soon->sum('passengers_count'), $soon->count().' '.str('departure')->plural($soon->count()).' in the next 7 days', 'info', 'ri-group-line', '#trips'];
        }

        if ($user->can('list-payments') && $user->can('view-financial-info')) {
            $pending = BookingPayment::where('status', 'pending')->selectRaw('count(*) n, coalesce(sum(amount), 0) total')->first();
            $overdue = Booking::where('status', '!=', 'cancelled')->where('outstanding_amount', '>', 0)->whereNotNull('balance_due_date')->where('balance_due_date', '<', $today)
                ->selectRaw('count(*) n, coalesce(sum(outstanding_amount), 0) total')->first();
            $from = now($tz)->subDays(self::RECENT_PAYMENT_DAYS - 1)->toDateString();
            $tiles[] = ['Waiting for verification', (int) $pending->n, money($pending->total).' not yet counted', $pending->n ? 'warning' : 'secondary', 'ri-time-line', route('payments.index', ['tab' => 'pending'])];
            $tiles[] = ['Overdue balances', (int) $overdue->n, money($overdue->total).' past the due date', $overdue->n ? 'danger' : 'secondary', 'ri-error-warning-line', route('payments.index', ['tab' => 'outstanding', 'overdue' => 1])];
            $out['payments'] = [
                'recent' => BookingPayment::where('status', 'confirmed')->where('kind', 'payment')->where('paid_on', '>=', $from)->with('booking')->orderByDesc('paid_on')->orderByDesc('id')->limit(6)->get(),
                'recentTotal' => BookingPayment::where('status', 'confirmed')->where('kind', 'payment')->where('paid_on', '>=', $from)->sum('amount'),
                'recentUrl' => route('payments.index', ['tab' => 'ledger', 'from' => $from, 'to' => $today, 'kind' => 'payment', 'status' => 'confirmed']),
            ];
        }

        if ($user->can('list-activity-log')) {
            $failed = ActivityLog::where('action', 'email_failed')->where('created_at', '>=', now()->subDays(self::FAILED_EMAIL_DAYS))->count();
            $tiles[] = ['Failed emails', $failed, 'in the last '.self::FAILED_EMAIL_DAYS.' days', $failed ? 'danger' : 'secondary', 'ri-mail-close-line', route('activity-log.index', ['action' => 'email_failed'])];
        }

        return ['tiles' => $tiles] + $out;
    }

    /** Departures in the next TRIP_DAYS days with passengers, seats left, unpaid bookings and seating progress. */
    public static function trips(): Collection
    {
        $pax = fn (bool $seated) => BookingPassenger::selectRaw('count(*)')->join('bookings', 'bookings.id', '=', 'booking_passengers.booking_id')
            ->whereColumn('bookings.tour_departure_id', 'tour_departures.id')->where('bookings.status', '!=', 'cancelled')
            ->when($seated, fn ($q) => $q->whereNotNull('booking_passengers.departure_vehicle_id')->whereNotNull('booking_passengers.seat_number'));

        return TourDeparture::query()->select('tour_departures.*')
            ->selectSub($pax(false), 'passengers_count')
            ->selectSub($pax(true), 'seated_count')
            ->selectSub(DepartureVehicle::selectRaw('count(*)')->whereColumn('tour_departure_id', 'tour_departures.id'), 'vehicles_count')
            ->selectSub(Booking::selectRaw('count(*)')->whereColumn('tour_departure_id', 'tour_departures.id')->where('status', '!=', 'cancelled')->where('outstanding_amount', '>', 0), 'unpaid_count')
            ->whereBetween('departs_at', [now()->startOfDay(), now()->addDays(self::TRIP_DAYS)->endOfDay()])
            ->whereNotIn('status', ['cancelled', 'hidden'])
            ->with(['tour.translations', 'staff.user'])
            ->orderBy('departs_at')->limit(12)->get();
    }
}
