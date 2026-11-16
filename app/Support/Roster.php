<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\TourDeparture;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The passenger list of one departure (CRM §11), shared by the office roster, the leader's
 * trip page and the exports. Rows are ['passenger' => BookingPassenger, 'booking' => Booking]
 * for every traveller on a booking that is not cancelled.
 */
class Roster
{
    public const SORTS = ['pickup' => 'Pickup stop', 'name' => 'Name', 'seat' => 'Seat', 'registered' => 'Booked (oldest first)', 'balance' => 'Balance (highest)'];

    public const ATTENDANCE_FILTERS = ['missing' => 'Still missing', 'arrived' => 'Arrived (checked in or boarded)', 'expected' => 'No mark yet'] + [
        'checked_in' => 'Checked in', 'boarded' => 'Boarded', 'not_arrived' => 'Not arrived', 'no_show' => 'No-show',
    ];

    public static function load(TourDeparture $departure): TourDeparture
    {
        return $departure->loadMissing([
            'tour.translations', 'tour.pickupPoints.translations', 'pickupPoints.translations', 'staff.user',
            'bookings' => fn ($q) => $q->orderBy('created_at')->orderBy('id'),
            'bookings.passengers' => fn ($q) => $q->orderBy('id'),
            'bookings.passengers.pickupPoint.translations', 'bookings.passengers.attendanceBy', 'bookings.passengers.departureVehicle', 'vehicles',
        ]);
    }

    /** @return Collection<int, array{passenger: mixed, booking: Booking}> every passenger, unfiltered */
    public static function all(TourDeparture $departure): Collection
    {
        return self::load($departure)->bookings->where('status', '!=', 'cancelled')
            ->flatMap(fn ($b) => $b->passengers->map(fn ($p) => ['passenger' => $p, 'booking' => $b]))->values();
    }

    /** Pickup stops in the departure's order (else the tour's), then any other stop a passenger chose. */
    public static function stops(TourDeparture $departure, ?Collection $rows = null): Collection
    {
        self::load($departure);
        $rows ??= self::all($departure);

        return ($departure->pickupPoints->isNotEmpty() ? $departure->pickupPoints : $departure->tour->pickupPoints)
            ->concat($rows->pluck('passenger.pickupPoint')->filter())->unique('id')->values();
    }

    /** Filter and sort; money sorts are ignored without view-financial-info. */
    public static function filter(Collection $rows, array $f, Collection $stops, User $user): Collection
    {
        $q = mb_strtolower(trim((string) ($f['q'] ?? '')));
        $digits = preg_replace('/\D/', '', $q);
        $rows = $rows
            ->when($q !== '', fn ($r) => $r->filter(function ($row) use ($q, $digits) {
                [$p, $b] = [$row['passenger'], $row['booking']];
                $text = mb_strtolower("{$p->first_name} {$p->last_name} {$b->first_name} {$b->last_name} {$b->reference}");

                return str_contains($text, $q) || (strlen($digits) >= 3 && str_contains(preg_replace('/\D/', '', $p->phone.' '.$b->phone), $digits));
            }))
            ->when(($f['pickup'] ?? '') !== '', fn ($r) => $r->filter(fn ($row) => (string) ($row['passenger']->pickup_point_id ?? 0) === (string) $f['pickup']))
            ->when($f['attendance'] ?? null, fn ($r, $a) => $r->filter(fn ($row) => match ($a) {
                'missing' => Attendance::isMissing($row['passenger']),
                'arrived' => in_array($row['passenger']->attendance_status, ['checked_in', 'boarded'], true),
                'expected' => $row['passenger']->attendance_status === null,
                default => $row['passenger']->attendance_status === $a,
            }))
            ->when(($f['vehicle'] ?? '') !== '', fn ($r) => $r->filter(fn ($row) => (string) ($row['passenger']->departure_vehicle_id ?? 0) === (string) $f['vehicle']))
            ->when($f['meal'] ?? null, fn ($r, $m) => $r->filter(fn ($row) => $m === '—' ? blank($row['passenger']->meal_selection) : $row['passenger']->meal_selection === $m))
            ->when($f['booking_status'] ?? null, fn ($r, $s) => $r->filter(fn ($row) => $row['booking']->status === $s));

        $stopOrder = $stops->pluck('id')->flip();
        $name = fn ($row) => mb_strtolower($row['passenger']->last_name.' '.$row['passenger']->first_name);
        $sort = $f['sort'] ?? 'pickup';
        if ($sort === 'balance' && !$user->can('view-financial-info')) {
            $sort = 'pickup';
        }

        return $rows->sortBy(match ($sort) {
            'name' => [fn ($a, $b) => strcmp($name($a), $name($b))],
            'seat' => [fn ($a, $b) => strnatcmp((string) ($a['passenger']->seat_number ?: '~'), (string) ($b['passenger']->seat_number ?: '~'))],
            'registered' => [fn ($a, $b) => [$a['booking']->created_at, $a['booking']->id] <=> [$b['booking']->created_at, $b['booking']->id]],
            'balance' => [fn ($a, $b) => (float) $b['booking']->outstanding_amount <=> (float) $a['booking']->outstanding_amount],
            default => [fn ($a, $b) => [$stopOrder[$a['passenger']->pickup_point_id] ?? PHP_INT_MAX, $name($a)] <=> [$stopOrder[$b['passenger']->pickup_point_id] ?? PHP_INT_MAX, $name($b)]],
        })->values();
    }

    /**
     * Export columns. A leader's copy carries only what the trip needs (§11 "only data required
     * by recipient"); booking status and registration date are for the office, money for finance.
     *
     * @return array<string, callable>
     */
    public static function columns(User $user, bool $office): array
    {
        $tz = \App\Http\Controllers\Admin\ActivityLogController::DISPLAY_TIMEZONE;
        $columns = [
            'Passenger' => fn ($p, $b) => trim("{$p->first_name} {$p->last_name}"),
            'Category' => fn ($p, $b) => $p->category && $p->category !== 'adult' ? $p->category : '',
            'Phone' => fn ($p, $b) => $p->phone ?: $b->phone,
            'Pickup' => fn ($p, $b) => $p->pickupPoint?->name,
            'Seat' => fn ($p, $b) => Seating::label($p->departureVehicle, $p->seat_number),
            'Meal' => fn ($p, $b) => $p->meal_selection,
            'Note' => fn ($p, $b) => $p->notes,
            'Booking' => fn ($p, $b) => $b->reference,
            'Attendance' => fn ($p, $b) => $p->attendance_status ? Attendance::label($p->attendance_status).' '.$p->attendance_at?->timezone($tz)->format('g:i A') : '',
        ];
        if ($office) {
            $columns += [
                'Booking status' => fn ($p, $b) => Booking::STATUSES[$b->status] ?? $b->status,
                'Booked on' => fn ($p, $b) => $b->created_at?->timezone($tz)->format('Y-m-d'),
            ];
        }
        if ($office && $user->can('view-financial-info')) {
            $columns += [
                'Payment' => fn ($p, $b) => Booking::PAYMENT_STATUSES[$b->payment_status] ?? $b->payment_status,
                'Booking balance (CAD)' => fn ($p, $b) => number_format((float) $b->outstanding_amount, 2, '.', ''),
            ];
        }

        return $columns;
    }
}
