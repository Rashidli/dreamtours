<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\DepartureVehicle;
use App\Models\TourDeparture;
use App\Models\User;
use App\Models\VehicleLayout;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Seat assignment for a departure's buses (CRM §9, AT-06). Every change goes through place(),
 * which locks the departure, re-reads who sits where and applies all moves of one action
 * (a move, a swap, an undo, an auto-fill) together, so two passengers never share a seat and
 * two people editing at once cannot overwrite each other unnoticed.
 */
class Seating
{
    /** Pickup stop colours on seat cards, in stop order. */
    public const COLOURS = ['#0f9cf3', '#2fb36a', '#f59f00', '#e0314f', '#7c4dff', '#00a3b4', '#ff7043', '#6d7a8a'];

    public static function canView(User $user, TourDeparture $departure): bool
    {
        return $user->can('list-bookings') || $departure->hasStaff($user);
    }

    public static function canEdit(User $user): bool
    {
        return $user->can('manage-seating');
    }

    /** "Bus 1 · 12" (or just "12" when the date has one bus). */
    public static function label(?DepartureVehicle $vehicle, ?string $seat, bool $withBus = true): ?string
    {
        if ($seat === null || $seat === '') {
            return null;
        }

        return $vehicle && $withBus ? $vehicle->label.' · '.$seat : $seat;
    }

    /** Passengers on bookings that are not cancelled, in stop order then booking order. */
    public static function passengers(TourDeparture $departure): Collection
    {
        $stops = Roster::stops($departure)->pluck('id')->flip();

        return BookingPassenger::query()
            ->whereHas('booking', fn ($q) => $q->where('tour_departure_id', $departure->id)->where('status', '!=', 'cancelled'))
            ->with(['booking', 'pickupPoint.translations', 'departureVehicle'])
            ->get()
            ->sortBy([fn ($a, $b) => [$stops[$a->pickup_point_id] ?? PHP_INT_MAX, $a->booking->created_at, $a->booking_id, $a->id] <=> [$stops[$b->pickup_point_id] ?? PHP_INT_MAX, $b->booking->created_at, $b->booking_id, $b->id]])
            ->values();
    }

    /** Everything the seating board needs, filtered to what this person may see. */
    public static function state(TourDeparture $departure, User $user): array
    {
        $departure->unsetRelation('vehicles');
        $vehicles = $departure->vehicles()->get();
        $stops = Roster::stops($departure)->values();
        $colour = $stops->mapWithKeys(fn ($s, $i) => [$s->id => self::COLOURS[$i % count(self::COLOURS)]]);
        $office = $user->can('list-bookings');
        $finance = $office && $user->can('view-financial-info');

        return [
            'canEdit' => self::canEdit($user),
            'vehicles' => $vehicles->map(fn (DepartureVehicle $v) => [
                'id' => $v->id, 'label' => $v->label, 'note' => $v->note, 'layout' => $v->layout, 'blocked' => $v->blockedSeats(),
                'seats' => count($v->seatLabels()), 'draft' => $v->hasDraftChanges(),
                'published' => $v->published_at?->timezone(\App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE)->format('M j, g:i A'),
            ])->values(),
            'publish' => (function () use ($departure, $vehicles) {
                $changed = self::bookingsToNotify($departure);
                $ready = $changed->filter(fn ($b) => in_array($b->payment_status, ['paid', 'complimentary'], true) && !$b->passengers->contains(fn ($p) => !$p->departure_vehicle_id))->count();

                return ['draft' => $vehicles->contains(fn ($v) => $v->hasDraftChanges()), 'changed' => $changed->count(), 'ready' => $ready];
            })(),
            'stops' => $stops->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'colour' => $colour[$s->id]])->values(),
            'passengers' => self::passengers($departure)->map(fn (BookingPassenger $p) => array_filter([
                'id' => $p->id,
                'name' => trim("{$p->first_name} {$p->last_name}"),
                'short' => trim($p->first_name.' '.mb_substr((string) $p->last_name, 0, 1).($p->last_name ? '.' : '')),
                'category' => $p->category !== 'adult' ? $p->category : null,
                'booking' => $p->booking->reference,
                'bookingId' => $p->booking_id,
                'bookingUrl' => $office ? route('bookings.show', $p->booking_id) : null,
                'stop' => $p->pickup_point_id,
                'stopName' => $p->pickupPoint?->name,
                'colour' => $colour[$p->pickup_point_id] ?? '#adb5bd',
                'meal' => $p->meal_selection,
                'phone' => $p->phone ?: $p->booking->phone,
                'payment' => $finance ? (Booking::PAYMENT_STATUSES[$p->booking->payment_status] ?? $p->booking->payment_status) : null,
                'vehicle' => $p->departure_vehicle_id,
                'seat' => $p->seat_number,
            ], fn ($v) => $v !== null))->values(),
        ];
    }

    /**
     * Apply one action: each placement is [p => passenger id, v => vehicle id|null, s => seat|null].
     * Seats taken by anyone not in the list are refused, except seats still held by a cancelled booking.
     *
     * @param  array<int, array{p: int, v: ?int, s: ?string}>  $placements
     * @return int number of passengers whose seat changed
     */
    public static function place(TourDeparture $departure, array $placements, User $by): int
    {
        return DB::transaction(function () use ($departure, $placements, $by) {
            TourDeparture::whereKey($departure->id)->lockForUpdate()->first();
            $vehicles = $departure->vehicles()->get()->keyBy('id');
            $ids = array_map(fn ($x) => (int) $x['p'], $placements);
            if (count($ids) !== count(array_unique($ids))) {
                throw ValidationException::withMessages(['seat' => 'A passenger appears twice in one change.']);
            }
            $passengers = BookingPassenger::whereIn('id', $ids)->with(['booking', 'departureVehicle'])->lockForUpdate()->get()->keyBy('id');

            $targets = [];
            $want = []; // passenger id => [vehicle|null, seat|null]
            foreach ($placements as $x) {
                $p = $passengers->get((int) $x['p']);
                if (!$p || $p->booking?->tour_departure_id !== $departure->id || $p->booking->status === 'cancelled') {
                    throw ValidationException::withMessages(['seat' => 'That passenger is not on this date any more. Reload the page.']);
                }
                $v = $x['v'] !== null && $x['v'] !== '' ? $vehicles->get((int) $x['v']) : null;
                $s = $x['s'] !== null && $x['s'] !== '' ? (string) $x['s'] : null;
                if (($v === null) !== ($s === null) || ($x['v'] && !$v)) {
                    throw ValidationException::withMessages(['seat' => 'Choose a bus and a seat.']);
                }
                if ($v && !in_array($s, $v->seatLabels(), true)) {
                    throw ValidationException::withMessages(['seat' => "{$v->label} has no seat {$s}."]);
                }
                if ($v && in_array($s, $v->blockedSeats(), true)) {
                    throw ValidationException::withMessages(['seat' => "Seat {$s} on {$v->label} is blocked."]);
                }
                if ($v) {
                    $key = $v->id.'|'.$s;
                    if (isset($targets[$key])) {
                        throw ValidationException::withMessages(['seat' => "Two passengers were sent to seat {$s} on {$v->label}."]);
                    }
                    $targets[$key] = $p->id;
                }
                $want[$p->id] = [$v, $s];
            }

            // Whoever else sits on a target seat blocks the change, unless their booking was cancelled.
            if ($targets) {
                $occupants = BookingPassenger::whereIn('departure_vehicle_id', $vehicles->keys())->whereNotNull('seat_number')
                    ->whereNotIn('id', $ids)->with('booking')->lockForUpdate()->get()
                    ->filter(fn ($o) => isset($targets[$o->departure_vehicle_id.'|'.$o->seat_number]));
                foreach ($occupants as $o) {
                    if ($o->booking?->status !== 'cancelled') {
                        throw ValidationException::withMessages(['seat' => 'Seat '.$o->seat_number.' on '.$vehicles[$o->departure_vehicle_id]->label.' was just taken by '.trim("{$o->first_name} {$o->last_name}").'. The board has been refreshed.']);
                    }
                    $o->forceFill(['departure_vehicle_id' => null, 'seat_number' => null])->saveQuietly();
                }
            }

            // Free every seat first so swaps never collide on the unique index, then seat everyone.
            $changed = $passengers->filter(fn ($p) => [$p->departure_vehicle_id, $p->seat_number] !== [$want[$p->id][0]?->id, $want[$p->id][1]]);
            $before = $changed->mapWithKeys(fn ($p) => [$p->id => self::label($p->departureVehicle, $p->seat_number)]);
            $touched = $changed->pluck('departure_vehicle_id')->all();
            $changed->each(fn ($p) => $p->forceFill(['departure_vehicle_id' => null, 'seat_number' => null])->saveQuietly());
            foreach ($changed as $p) {
                [$v, $s] = $want[$p->id];
                $p->forceFill(['departure_vehicle_id' => $v?->id, 'seat_number' => $s])->saveQuietly();
                $p->setRelation('departureVehicle', $v);
                Activity::record('seat_changed', $p, ['seat' => [$before[$p->id], self::label($v, $s)]]);
                $touched[] = $v?->id;
            }
            $touched = array_values(array_filter(array_unique($touched)));
            if ($touched) {
                DepartureVehicle::whereIn('id', $touched)->update(['seats_changed_at' => now()]);
            }

            return $changed->count();
        });
    }

    /** Block or free a seat for this date. A taken seat has to be emptied first. */
    public static function block(DepartureVehicle $vehicle, string $seat, bool $block): void
    {
        DB::transaction(function () use ($vehicle, $seat, $block) {
            $vehicle = DepartureVehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
            if (!in_array($seat, $vehicle->seatLabels(), true)) {
                throw ValidationException::withMessages(['seat' => "{$vehicle->label} has no seat {$seat}."]);
            }
            if ($block && $vehicle->passengers()->where('seat_number', $seat)->whereHas('booking', fn ($q) => $q->where('status', '!=', 'cancelled'))->exists()) {
                throw ValidationException::withMessages(['seat' => "Seat {$seat} is taken. Move the passenger first."]);
            }
            $blocked = collect($vehicle->blockedSeats())->reject(fn ($s) => $s === $seat);
            if ($block) {
                $blocked->push($seat);
            }
            $vehicle->blocked = $blocked->values()->all();
            $vehicle->save();
        });
    }

    /**
     * Seat everyone without a seat (optionally one pickup stop, optionally one bus) in plan order,
     * stop by stop and booking by booking, so groups and families sit together.
     */
    public static function autofill(TourDeparture $departure, ?int $vehicleId, ?int $stopId, User $by): int
    {
        $vehicles = $departure->vehicles()->get()->when($vehicleId, fn ($c) => $c->where('id', $vehicleId));
        $taken = BookingPassenger::whereIn('departure_vehicle_id', $departure->vehicles()->pluck('id'))->whereNotNull('seat_number')
            ->whereHas('booking', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->get()->map(fn ($p) => $p->departure_vehicle_id.'|'.$p->seat_number)->flip();
        $free = $vehicles->flatMap(fn ($v) => collect($v->openSeatLabels())->reject(fn ($s) => $taken->has($v->id.'|'.$s))->map(fn ($s) => [$v->id, $s]))->values();
        $waiting = self::passengers($departure)->filter(fn ($p) => !$p->seat_number || !$p->departure_vehicle_id)
            ->when($stopId !== null, fn ($c) => $c->where('pickup_point_id', $stopId ?: null))->values();
        $placements = $waiting->take($free->count())->map(fn ($p, $i) => ['p' => $p->id, 'v' => $free[$i][0], 's' => $free[$i][1]])->all();

        return $placements ? self::place($departure, $placements, $by) : 0;
    }

    /** A cancelled booking gives its seats back. */
    public static function release(Booking $booking): void
    {
        $booking->passengers()->whereNotNull('departure_vehicle_id')->with('departureVehicle')->get()->each(function (BookingPassenger $p) {
            Activity::record('seat_changed', $p, ['seat' => [self::label($p->departureVehicle, $p->seat_number), null]], reason: 'Booking cancelled');
            DepartureVehicle::whereKey($p->departure_vehicle_id)->update(['seats_changed_at' => now()]);
            $p->forceFill(['departure_vehicle_id' => null, 'seat_number' => null])->saveQuietly();
        });
    }

    /**
     * Publish the final seating of a date (CRM §9). With $notify, email seat numbers to every booking
     * that can receive them and whose seats are new or changed since it was last told.
     *
     * @return array{sent: int, waiting: int}
     */
    public static function publish(TourDeparture $departure, User $by, bool $notify): array
    {
        $departure->vehicles()->update(['published_at' => now(), 'published_by' => $by->id, 'seats_changed_at' => null]);
        $departure->unsetRelation('vehicles');
        $sent = 0;
        $waiting = 0;
        if ($notify) {
            foreach (self::bookingsToNotify($departure) as $booking) {
                if (SeatNotices::blocker($booking)) {
                    $waiting++;
                    continue;
                }
                SeatNotices::send($booking);
                $sent++;
            }
        }
        Activity::record('seating_published', $departure, ['buses' => [null, (string) $departure->vehicles()->count()], 'seat emails' => [null, $notify ? (string) $sent : 'not sent']]);

        return ['sent' => $sent, 'waiting' => $waiting];
    }

    /** Bookings on this date whose seats changed since the guest was last told. */
    public static function bookingsToNotify(TourDeparture $departure): Collection
    {
        return Booking::where('tour_departure_id', $departure->id)->where('status', '!=', 'cancelled')
            ->with(['passengers.departureVehicle', 'departure.vehicles'])->get()
            ->filter(fn ($b) => $b->passengers->isNotEmpty() && SeatNotices::changed($b))->values();
    }

    /** Copy a template onto a date as its next bus. */
    public static function addVehicle(TourDeparture $departure, VehicleLayout $template, ?string $label = null, ?string $note = null): DepartureVehicle
    {
        $n = $departure->vehicles()->count() + 1;

        return $departure->vehicles()->create([
            'vehicle_layout_id' => $template->id,
            'label' => $label ?: 'Bus '.$n,
            'note' => $note,
            'layout' => $template->layout,
            'blocked' => [],
            'sort_order' => $n,
        ]);
    }
}
