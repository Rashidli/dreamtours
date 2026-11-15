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
 * Contact, call-back, private-tour and quick tour requests: an acknowledgement for
 * the customer and, with $forAdmin, an alert for the office.
 */
class EnquiryReceived extends Mailable implements ShouldQueue
{
    use Queueable, RetriesDelivery, SerializesModels;

    public const LABELS = [
        'contact' => 'Contact message',
        'callback' => 'Call-back request',
        'private_tour' => 'Private tour request',
        'tour' => 'Tour request',
        'cruise' => 'Cruise request',
        'invoice' => 'Invoice request',
        'visa' => 'Visa request',
    ];

    public function __construct(public Booking $booking, public bool $forAdmin = false) {}

    public function label(): string
    {
        return self::LABELS[$this->booking->type] ?? 'Request';
    }

    public function envelope(): Envelope
    {
        $about = $this->booking->tour?->name;

        return new Envelope(
            subject: $this->forAdmin
                ? "New ".strtolower($this->label())." from {$this->booking->first_name} {$this->booking->last_name}".($about ? " — {$about}" : '')
                : 'We received your '.strtolower($this->label()).($about ? " — {$about}" : ''),
            replyTo: $this->forAdmin ? [new Address($this->booking->email, trim("{$this->booking->first_name} {$this->booking->last_name}"))] : array_filter([setting('email')]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.enquiry-received', with: ['label' => $this->label()]);
    }
}
