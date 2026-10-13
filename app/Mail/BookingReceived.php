<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the customer (and, with $forAdmin, to the office) when a tour booking is submitted. */
class BookingReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking, public bool $forAdmin = false) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->forAdmin
            ? "New booking {$this->booking->reference} — {$this->booking->tour?->name}"
            : "Booking received — {$this->booking->reference}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.booking-received');
    }
}
