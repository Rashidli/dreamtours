<?php

namespace App\Mail;

use App\Mail\Concerns\RetriesDelivery;
use App\Models\WaitlistEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent when staff offer a waitlisted guest a seat that has become free (CRM §8). */
class WaitlistSeatAvailable extends Mailable implements ShouldQueue
{
    use Queueable, RetriesDelivery, SerializesModels;

    public function __construct(public WaitlistEntry $entry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A seat is available — '.$this->entry->departure?->tour?->name,
            replyTo: array_filter([setting('email')]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.waitlist-seat');
    }
}
