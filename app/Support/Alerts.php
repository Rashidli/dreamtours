<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\StaffAlert;
use App\Models\TourDeparture;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Staff alerts (CRM §15), in-app for now. raise() is idempotent per dedupe key, so the same event
 * never alerts twice; resolve() closes an alert when the underlying work is done elsewhere
 * (a payment confirmed, a lead contacted). Model events call these, so every path counts.
 */
class Alerts
{
    /** type => [label, icon, colour, permission needed to see it] */
    public const TYPES = [
        'new_inquiry' => ['New inquiry', 'ri-funnel-line', 'primary', 'list-leads'],
        'payment_pending' => ['Payment to verify', 'ri-time-line', 'warning', 'list-payments'],
        'balance_due_soon' => ['Balance due soon', 'ri-calendar-event-line', 'info', 'list-payments'],
        'booking_cancelled' => ['Cancellation', 'ri-close-circle-line', 'danger', 'list-bookings'],
        'tour_almost_full' => ['Tour almost full', 'ri-bus-line', 'warning', 'list-bookings'],
        'tour_full' => ['Tour full', 'ri-bus-fill', 'danger', 'list-bookings'],
        'upcoming_tour' => ['Upcoming tour', 'ri-map-2-line', 'info', 'list-bookings'],
        'waitlist_joined' => ['Joined the waitlist', 'ri-user-add-line', 'primary', 'list-bookings'],
        'waitlist_seat_free' => ['Seat free for the waitlist', 'ri-user-star-line', 'success', 'list-bookings'],
        'email_failed' => ['Email not delivered', 'ri-mail-close-line', 'danger', 'list-bookings'],
        'support_request' => ['Contact request', 'ri-mail-line', 'primary', 'list-contacts'],
    ];

    /** How often the time-based checks (balance due, upcoming tours) run while staff use the admin. */
    public const SWEEP_MINUTES = 10;

    public static function enabled(string $type): bool
    {
        return !in_array($type, array_filter(explode(',', (string) setting('alerts_off', ''))), true);
    }

    public static function raise(string $type, string $key, string $title, ?string $body = null, ?string $url = null, ?Model $subject = null, ?int $assignedTo = null): void
    {
        if (!isset(self::TYPES[$type]) || !self::enabled($type)) {
            return;
        }
        try {
            DB::table('staff_alerts')->insertOrIgnore([
                'type' => $type, 'dedupe_key' => mb_substr($key, 0, 150), 'title' => mb_substr($title, 0, 255), 'body' => $body ? mb_substr($body, 0, 255) : null,
                'url' => $url, 'subject_type' => $subject?->getMorphClass(), 'subject_id' => $subject?->getKey(), 'permission' => self::TYPES[$type][3],
                'assigned_user_id' => $assignedTo, 'created_at' => now(), 'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            report($e); // an alert must never break the action that raised it
        }
    }

    public static function resolve(string $key, string $note, ?User $by = null): void
    {
        StaffAlert::where('dedupe_key', $key)->open()->update(['completed_at' => now(), 'completed_by' => $by?->id, 'completed_note' => mb_substr($note, 0, 255), 'updated_at' => now()]);
    }

    /** Model events that raise and close alerts. Called once from AppServiceProvider::boot(). */
    public static function listen(): void
    {
        Lead::created(function (Lead $lead) {
            if (auth()->check()) {
                return; // staff typing in a lead don't need to be told about it
            }
            self::raise('new_inquiry', "lead:{$lead->id}", 'New inquiry: '.$lead->full_name, collect([$lead->tour?->name, $lead->guests ? $lead->guests.' guests' : null, $lead->sourceLabel()])->filter()->implode(' · '), route('leads.show', $lead), $lead, $lead->assigned_user_id);
        });
        Lead::updated(function (Lead $lead) {
            if ($lead->wasChanged('lead_stage_id')) {
                self::resolve("lead:{$lead->id}", 'Moved to '.($lead->stage?->name ?? 'another stage'), auth()->user());
            }
        });

        BookingPayment::created(function (BookingPayment $p) {
            if ($p->status === 'pending' && $p->kind === 'payment') {
                $b = $p->booking;
                self::raise('payment_pending', "payment:{$p->id}", money($p->amount, $p->currency).' to verify — '.$b?->reference, trim($b?->first_name.' '.$b?->last_name).' · '.(BookingPayment::METHODS[$p->method] ?? $p->method), route('payments.index', ['tab' => 'pending']), $p);
            }
        });
        BookingPayment::updated(function (BookingPayment $p) {
            if ($p->wasChanged('status') && $p->getOriginal('status') === 'pending') {
                self::resolve("payment:{$p->id}", 'Payment '.$p->status, auth()->user());
            }
        });

        Booking::updated(function (Booking $b) {
            if ($b->wasChanged('status') && $b->status === 'cancelled') {
                self::raise('booking_cancelled', "cancelled:{$b->id}:".now()->format('YmdHi'), 'Cancelled: '.$b->reference.' · '.trim("{$b->first_name} {$b->last_name}"), collect([$b->tour?->name, $b->departure?->departs_at?->format('M j'), $b->participant_count.' '.str('guest')->plural($b->participant_count)])->filter()->implode(' · '), route('bookings.show', $b), $b);
            }
        });

        // Seat counts are saved quietly by recountSeats(), which calls checkCapacity() itself.
        TourDeparture::updated(function (TourDeparture $d) {
            if ($d->wasChanged(['held_seats', 'capacity'])) {
                self::checkCapacity($d);
            }
        });

        Contact::created(fn (Contact $c) => self::raise('support_request', "contact:{$c->id}", 'Contact request from '.$c->name, str($c->message)->limit(120)->toString(), route('contacts.index'), $c));
        Contact::updated(function (Contact $c) {
            if ($c->wasChanged('is_read') && $c->is_read) {
                self::resolve("contact:{$c->id}", 'Read', auth()->user());
            }
        });
    }

    /** Full or almost full (threshold in Settings); a full alert closes itself when seats free up. */
    public static function checkCapacity(TourDeparture $d): void
    {
        if (!$d->departs_at || $d->departs_at->isPast() || in_array($d->status, ['cancelled', 'hidden'], true)) {
            return;
        }
        $left = $d->available_seats;
        $what = ($d->tour?->name ?? 'Tour').' · '.$d->departs_at->format('D, M j');
        if ($left <= 0) {
            self::raise('tour_full', "full:{$d->id}", 'Full: '.$what, "{$d->capacity} seats taken", route('tour-departures.passengers', $d), $d);

            return;
        }
        self::resolve("full:{$d->id}", 'Seats freed up');
        Waitlist::seatsFreed($d);
        if ($left <= (int) setting('alert_almost_full_seats', 5)) {
            self::raise('tour_almost_full', "almost-full:{$d->id}", 'Almost full: '.$what, "{$left} of {$d->capacity} seats left", route('tour-departures.passengers', $d), $d);
        }
    }

    /**
     * Time-based alerts that no single save triggers: balances due within N days and tours leaving
     * within N days. Runs at most every SWEEP_MINUTES while staff use the admin (and from
     * crm:alerts once a scheduler runs), closing the ones that no longer apply.
     */
    public static function sweep(): void
    {
        $tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE;
        $today = now($tz)->toDateString();
        $dueUntil = now($tz)->addDays((int) setting('alert_balance_due_days', 3))->toDateString();
        Booking::where('status', '!=', 'cancelled')->where('outstanding_amount', '>', 0)->whereBetween('balance_due_date', [$today, $dueUntil])->with('tour.translations')->get()
            ->each(fn ($b) => self::raise('balance_due_soon', "due:{$b->id}:{$b->balance_due_date->toDateString()}", money($b->outstanding_amount, $b->currency).' due '.$b->balance_due_date->format('M j').' — '.$b->reference, trim("{$b->first_name} {$b->last_name}").($b->tour ? ' · '.$b->tour->name : ''), route('bookings.show', $b), $b));
        StaffAlert::open()->where('type', 'balance_due_soon')->where('subject_type', (new Booking)->getMorphClass())
            ->whereIn('subject_id', Booking::where(fn ($q) => $q->where('outstanding_amount', '<=', 0)->orWhere('status', 'cancelled'))->select('id'))
            ->update(['completed_at' => now(), 'completed_note' => 'Paid or cancelled', 'updated_at' => now()]);

        $days = (int) setting('alert_upcoming_tour_days', 2);
        TourDeparture::whereBetween('departs_at', [now(), now()->addDays($days)->endOfDay()])->whereNotIn('status', ['cancelled', 'hidden'])
            ->whereHas('bookings', fn ($q) => $q->where('status', '!=', 'cancelled'))->with('tour.translations')->get()
            ->each(fn ($d) => self::raise('upcoming_tour', "upcoming:{$d->id}", 'Leaving '.($d->departs_at->isToday() ? 'today' : ($d->departs_at->isTomorrow() ? 'tomorrow' : $d->departs_at->format('D, M j'))).': '.$d->tour?->name, $d->bookedSeats().' passengers · departs '.$d->departs_at->format('g:i A'), route('tour-departures.passengers', $d), $d));
        StaffAlert::open()->where('type', 'upcoming_tour')->where('subject_type', (new TourDeparture)->getMorphClass())
            ->whereIn('subject_id', TourDeparture::where('departs_at', '<', now()->subDay())->select('id'))
            ->update(['completed_at' => now(), 'completed_note' => 'Trip has left', 'updated_at' => now()]);
    }

    public static function sweepIfDue(): void
    {
        if (Cache::add('staff-alerts-sweep', 1, now()->addMinutes(self::SWEEP_MINUTES))) {
            try {
                self::sweep();
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
