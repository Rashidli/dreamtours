<?php

namespace App\Support;

use App\Models\BookingPassenger;
use App\Models\PassengerAttendanceEvent;
use App\Models\TourDeparture;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Check-in on the day (CRM §11, AT-08). A passenger with no mark is "expected".
 * Moving forward (expected → arrived → boarded, or a late arrival) only needs check-in-passengers;
 * stepping back or clearing is an undo and needs undo-check-in, except that people may fix
 * their own mark for a few minutes.
 */
class Attendance
{
    /** status => [label, colour, icon] */
    public const STATUSES = [
        'checked_in' => ['Checked in', 'info', 'ri-user-follow-line'],
        'boarded' => ['Boarded', 'success', 'ri-bus-line'],
        'not_arrived' => ['Not arrived', 'warning', 'ri-time-line'],
        'no_show' => ['No-show', 'danger', 'ri-user-unfollow-line'],
    ];

    /** From a status (null = expected) to the marks that are progress rather than an undo. */
    private const FORWARD = [
        '' => ['checked_in', 'boarded', 'not_arrived', 'no_show'],
        'not_arrived' => ['checked_in', 'boarded', 'no_show'],
        'checked_in' => ['boarded'],
    ];

    public const OWN_UNDO_MINUTES = 10;

    /** Within this many minutes of departure, anyone still missing is flagged. */
    public const MISSING_ALERT_MINUTES = 30;

    public static function label(?string $status): string
    {
        return $status ? (self::STATUSES[$status][0] ?? $status) : 'Expected';
    }

    public static function isUndo(?string $from, ?string $to): bool
    {
        return $from !== $to && !in_array($to, self::FORWARD[$from ?? ''] ?? [], true);
    }

    /** Assigned staff, or office staff who can see bookings, with the check-in permission. */
    public static function canMark(User $user, TourDeparture $departure): bool
    {
        return $user->can('check-in-passengers') && ($user->can('list-bookings') || $departure->hasStaff($user));
    }

    public static function canUndo(User $user, BookingPassenger $passenger): bool
    {
        return $user->can('undo-check-in')
            || ($passenger->attendance_by === $user->id && $passenger->attendance_at?->gt(now()->subMinutes(self::OWN_UNDO_MINUTES)));
    }

    public static function mark(BookingPassenger $passenger, ?string $status, User $by, ?string $note = null): void
    {
        $from = $passenger->attendance_status;
        if ($from === $status && $note === null) {
            return;
        }
        if (self::isUndo($from, $status) && !self::canUndo($by, $passenger)) {
            throw ValidationException::withMessages(['attendance' => 'Changing “'.self::label($from).'” back needs the “Undo check-ins” permission (you can fix your own mark for '.self::OWN_UNDO_MINUTES.' minutes).']);
        }
        $passenger->forceFill([
            'attendance_status' => $status,
            // A note on the same mark keeps the original time and person.
            'attendance_at' => $status ? ($from === $status ? $passenger->attendance_at : now()) : null,
            'attendance_by' => $status ? ($from === $status ? $passenger->attendance_by : $by->id) : null,
            'attendance_note' => $status ? ($note ?? ($from === $status ? $passenger->attendance_note : null)) : null,
        ])->saveQuietly(); // the event below is the history
        PassengerAttendanceEvent::create([
            'booking_passenger_id' => $passenger->id,
            'tour_departure_id' => $passenger->booking?->tour_departure_id,
            'status' => $status,
            'previous_status' => $from,
            'note' => $note,
            'user_id' => $by->id,
        ]);
    }

    /**
     * Counts for a set of passengers: expected = everyone on the list, arrived = checked in or boarded,
     * missing = no mark yet or not arrived; no-shows are counted on their own and never as missing.
     *
     * @param  Collection<int, BookingPassenger>  $passengers
     * @return array{expected: int, arrived: int, boarded: int, missing: int, no_show: int}
     */
    public static function counts(Collection $passengers): array
    {
        $by = $passengers->countBy(fn ($p) => $p->attendance_status ?? 'expected');

        return [
            'expected' => $passengers->count(),
            'arrived' => ($by['checked_in'] ?? 0) + ($by['boarded'] ?? 0),
            'boarded' => $by['boarded'] ?? 0,
            'missing' => ($by['expected'] ?? 0) + ($by['not_arrived'] ?? 0),
            'no_show' => $by['no_show'] ?? 0,
        ];
    }

    public static function isMissing(BookingPassenger $passenger): bool
    {
        return in_array($passenger->attendance_status, [null, 'not_arrived'], true);
    }

    /** True from shortly before departure until the end of that day. */
    public static function alertWindow(TourDeparture $departure): bool
    {
        return now()->between($departure->departs_at->copy()->subMinutes(self::MISSING_ALERT_MINUTES), $departure->departs_at->copy()->endOfDay());
    }
}
