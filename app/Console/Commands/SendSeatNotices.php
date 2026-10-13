<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Support\SeatNotices;
use Illuminate\Console\Command;

class SendSeatNotices extends Command
{
    protected $signature = 'bookings:send-seat-notices {--days=2 : Send for departures within this many days}';
    protected $description = 'Email seat numbers to fully paid bookings departing soon';

    public function handle(): int
    {
        $bookings = Booking::query()
            ->whereNull('seat_notice_sent_at')
            ->where('payment_status', 'paid')
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('departure', fn ($q) => $q->whereBetween('departs_at', [now(), now()->addDays((int) $this->option('days'))->endOfDay()]))
            ->get();

        foreach ($bookings as $booking) {
            if ($reason = SeatNotices::blocker($booking)) {
                $this->warn("{$booking->reference}: {$reason}");
                continue;
            }
            SeatNotices::send($booking);
            $this->info("{$booking->reference}: seat numbers sent to {$booking->email}");
        }

        return self::SUCCESS;
    }
}
