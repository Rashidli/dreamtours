<?php

namespace App\Mail;

use App\Mail\Concerns\RetriesDelivery;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the customer (and, with $forAdmin, to the office) when a tour booking is
 * submitted. The booking is only a request until the office confirms it, so the
 * customer copy never says "confirmed" — BookingConfirmed does that later.
 */
class BookingReceived extends Mailable implements ShouldQueue
{
    use Queueable, RetriesDelivery, SerializesModels;

    public function __construct(public Booking $booking, public bool $forAdmin = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->forAdmin
                ? "New booking request {$this->booking->reference} — {$this->booking->tour?->name}"
                : "We received your booking request — {$this->booking->reference}",
            replyTo: $this->forAdmin ? [new Address($this->booking->email, trim("{$this->booking->first_name} {$this->booking->last_name}"))] : array_filter([setting('email')]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-received');
    }
}
