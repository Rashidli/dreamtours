<?php

namespace App\Support;

use App\Mail\BookingConfirmed;
use App\Mail\BookingReceived;
use App\Mail\EnquiryReceived;
use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Who gets which email for bookings and website requests. Every message is queued
 * and addressed to one recipient group, so customers never see each other's
 * addresses. A failure to queue is logged and never loses the booking itself.
 */
class BookingMail
{
    /** The office: the "Notification email" setting (comma-separated), else the main email. */
    public static function office(): array
    {
        return collect(explode(',', (string) (setting('notification_email') ?: setting('email'))))
            ->map(fn ($address) => trim($address))
            ->filter(fn ($address) => filter_var($address, FILTER_VALIDATE_EMAIL))
            ->unique()->values()->all();
    }

    /** A tour booking was submitted: acknowledgement for the customer, alert for the office. */
    public static function submitted(Booking $booking): void
    {
        $booking->loadMissing(['tour.translations', 'departure', 'pickupPoint.translations', 'passengers.pickupPoint.translations']);
        $customer = self::queue($booking->email, new BookingReceived($booking), $booking);
        self::queue(self::office(), new BookingReceived($booking, forAdmin: true), $booking);
        if ($customer) {
            $booking->forceFill(['confirmation_sent_at' => now()])->saveQuietly();
        }
    }

    /** A contact, call-back, private-tour or quick tour request was sent. */
    public static function enquiry(Booking $booking): void
    {
        $booking->loadMissing(['tour.translations']);
        self::queue($booking->email, new EnquiryReceived($booking), $booking);
        self::queue(self::office(), new EnquiryReceived($booking, forAdmin: true), $booking);
    }

    /** The office set the booking to Confirmed: tell the customer, once. */
    public static function confirmed(Booking $booking): void
    {
        if ($booking->status !== 'confirmed' || $booking->confirmed_notice_sent_at || !$booking->tour_departure_id) {
            return;
        }
        $booking->loadMissing(['tour.translations', 'departure', 'pickupPoint.translations', 'passengers.pickupPoint.translations']);
        if (self::queue($booking->email, new BookingConfirmed($booking), $booking)) {
            $booking->forceFill(['confirmed_notice_sent_at' => now()])->saveQuietly();
        }
    }

    private static function queue(string|array $to, Mailable $mail, Booking $booking): bool
    {
        if (empty($to)) {
            return false;
        }
        try {
            Mail::to($to)->queue($mail);

            return true;
        } catch (\Throwable $e) {
            Log::error('Booking email could not be queued', ['booking' => $booking->reference, 'mail' => $mail::class, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
