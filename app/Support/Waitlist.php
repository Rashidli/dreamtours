<?php

namespace App\Support;

use App\Mail\WaitlistSeatAvailable;
use App\Models\Booking;
use App\Models\TourDeparture;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * The waitlist of a departure (CRM §8): first come, first served unless staff reorder it.
 * When seats free up, staff are alerted about the next person who fits; they offer the seat
 * (optionally by email) and book it, which closes the entry.
 */
class Waitlist
{
    /** Only full, upcoming dates take a waitlist. */
    public static function open(TourDeparture $d): bool
    {
        return $d->departs_at?->isFuture() && $d->public_status === 'sold_out' && !$d->tour?->is_private;
    }

    public static function add(TourDeparture $d, array $data, ?User $by): WaitlistEntry
    {
        return DB::transaction(function () use ($d, $data, $by) {
            TourDeparture::whereKey($d->id)->lockForUpdate()->first();
            $email = filled($data['email'] ?? null) ? mb_strtolower(trim($data['email'])) : null;
            if ($email && WaitlistEntry::active()->where(['tour_departure_id' => $d->id, 'email' => $email])->exists()) {
                throw ValidationException::withMessages(['email' => 'This email is already on the waitlist for this date.']);
            }
            $customer = Customers::match(['first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => $email, 'phone' => $data['phone'] ?? null], $by ? 'manual' : 'website');
            $entry = WaitlistEntry::create([
                'tour_departure_id' => $d->id, 'customer_id' => $customer->id,
                'first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => $email, 'phone' => $data['phone'] ?? null,
                'guests' => max(1, (int) ($data['guests'] ?? 1)), 'note' => $data['note'] ?? null,
                'source' => $by ? 'staff' : 'website', 'created_by' => $by?->id,
                'position' => (int) WaitlistEntry::where('tour_departure_id', $d->id)->max('position') + 1,
            ]);
            if (!$by) {
                Alerts::raise('waitlist_joined', "waitlist-joined:{$entry->id}", 'Waitlist: '.$entry->full_name.' ('.$entry->guests.')', ($d->tour?->name ?? 'Tour').' · '.$d->departs_at->format('D, M j'), route('waitlist.index', ['departure' => $d->id]), $entry);
            }

            return $entry;
        });
    }

    public static function offer(WaitlistEntry $entry, User $by, bool $email): bool
    {
        abort_unless(in_array($entry->status, ['waiting', 'offered'], true), 422, 'This entry is closed.');
        $entry->forceFill(['status' => 'offered', 'offered_at' => now(), 'offered_by' => $by->id])->save();
        Alerts::resolve("waitlist-free:{$entry->id}", 'Seat offered', $by);
        if ($email && $entry->email) {
            Mail::to($entry->email)->send(new WaitlistSeatAvailable($entry->load('departure.tour')));
            Activity::record('email_queued', $entry, ['to' => $entry->email, 'email' => 'WaitlistSeatAvailable']);

            return true;
        }

        return false;
    }

    public static function booked(WaitlistEntry $entry, Booking $booking): void
    {
        $entry->forceFill(['status' => 'booked', 'booking_id' => $booking->id])->save();
        Alerts::resolve("waitlist-free:{$entry->id}", 'Booked as '.$booking->reference);
    }

    public static function remove(WaitlistEntry $entry, string $reason): void
    {
        $entry->forceFill(['status' => 'removed', 'removed_reason' => mb_substr($reason, 0, 255)])->save();
        Alerts::resolve("waitlist-free:{$entry->id}", 'Removed from the waitlist');
    }

    /** Swap with the neighbour above or below among the entries still in the queue. */
    public static function move(WaitlistEntry $entry, string $direction): void
    {
        DB::transaction(function () use ($entry, $direction) {
            $queue = WaitlistEntry::active()->where('tour_departure_id', $entry->tour_departure_id)->lockForUpdate()->get()->values();
            $i = $queue->search(fn ($e) => $e->id === $entry->id);
            $j = $direction === 'up' ? $i - 1 : $i + 1;
            if ($i === false || !isset($queue[$j])) {
                return;
            }
            // Renumber the whole queue so equal or missing positions can never tie.
            $order = $queue->pluck('id')->all();
            [$order[$i], $order[$j]] = [$order[$j], $order[$i]];
            foreach ($order as $n => $id) {
                WaitlistEntry::whereKey($id)->update(['position' => $n + 1]);
            }
        });
    }

    /** Seats freed on a date: alert about the first person in the queue whose party fits. */
    public static function seatsFreed(TourDeparture $d): void
    {
        $left = $d->available_seats;
        if ($left <= 0 || !$d->departs_at?->isFuture()) {
            return;
        }
        $next = WaitlistEntry::where(['tour_departure_id' => $d->id, 'status' => 'waiting'])->orderBy('position')->orderBy('id')->get()->first(fn ($e) => $e->guests <= $left);
        if ($next) {
            Alerts::raise('waitlist_seat_free', "waitlist-free:{$next->id}", 'Seat free for '.$next->full_name.' ('.$next->guests.') on the waitlist', ($d->tour?->name ?? 'Tour').' · '.$d->departs_at->format('D, M j')." · {$left} ".str('seat')->plural($left).' left', route('waitlist.index', ['departure' => $d->id]), $next);
        }
    }
}
