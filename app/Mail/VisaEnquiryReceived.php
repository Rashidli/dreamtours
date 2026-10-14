<?php

namespace App\Mail;

use App\Models\VisaEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Tells the office about a new visa support enquiry. */
class VisaEnquiryReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public VisaEnquiry $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "New visa enquiry {$this->enquiry->reference} — {$this->enquiry->country}", replyTo: [$this->enquiry->email]);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.visa-enquiry');
    }
}
