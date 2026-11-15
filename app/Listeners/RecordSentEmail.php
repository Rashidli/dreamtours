<?php

namespace App\Listeners;

use App\Support\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Events\MessageSent;

/**
 * Records every email the mail server accepted, against the booking or enquiry it
 * is about. "Queued" is recorded when the email is created; this is the "sent"
 * step, so the history never counts a queued email as delivered.
 */
class RecordSentEmail
{
    public function handle(MessageSent $event): void
    {
        $subject = collect([$event->data['booking'] ?? null, $event->data['enquiry'] ?? null])->first(fn ($item) => $item instanceof Model);
        $message = $event->sent->getOriginalMessage();

        Activity::record('email_sent', $subject, [
            'to' => collect($message->getTo())->map->getAddress()->implode(', '),
            'subject' => $message->getSubject(),
            'mailer' => config('mail.default'),
        ]);
    }
}
