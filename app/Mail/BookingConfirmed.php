<?php

namespace App\Mail;

use App\Mail\Concerns\RetriesDelivery;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the customer once, when the office sets the booking to Confirmed. */
class BookingConfirmed extends Mailable implements ShouldQueue
{
    use Queueable, RetriesDelivery, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your booking is confirmed — {$this->booking->tour?->name} ({$this->booking->reference})",
            replyTo: array_filter([setting('email')]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-confirmed');
    }
}
