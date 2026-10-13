<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Seat numbers and boarding details, sent 1–2 days before departure. */
class SeatAssignment extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your seat numbers — {$this->booking->tour?->name} ({$this->booking->reference})");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.seat-assignment');
    }
}
