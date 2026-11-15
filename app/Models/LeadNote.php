<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A note or contact attempt on a lead (call, email, WhatsApp…), with its outcome. */
class LeadNote extends Model
{
    public const KINDS = ['note' => ['Note', 'ri-sticky-note-line'], 'call' => ['Call', 'ri-phone-line'], 'whatsapp' => ['WhatsApp', 'ri-whatsapp-line'], 'email' => ['Email', 'ri-mail-line'], 'sms' => ['SMS', 'ri-message-2-line'], 'meeting' => ['Meeting', 'ri-team-line']];

    public const OUTCOMES = ['reached' => 'Reached', 'no_answer' => 'No answer', 'left_message' => 'Left a message', 'wrong_number' => 'Wrong number'];

    protected $fillable = ['lead_id', 'user_id', 'kind', 'outcome', 'body'];

    public function lead() { return $this->belongsTo(Lead::class); }
    public function user() { return $this->belongsTo(User::class); }

    public function isContactAttempt(): bool { return $this->kind !== 'note'; }
}
