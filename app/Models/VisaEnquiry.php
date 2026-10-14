<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VisaEnquiry extends Model
{
    protected $fillable = ['reference', 'full_name', 'email', 'phone', 'country', 'passport_country', 'assistance', 'applicants', 'travel_date', 'message', 'status', 'internal_notes'];

    protected $casts = ['assistance' => 'array', 'travel_date' => 'date', 'applicants' => 'integer'];

    public const STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'in_progress' => 'In progress', 'closed' => 'Closed'];

    /** What the visitor can ask for; the service is help with forms, documents and appointments only. */
    public const ASSISTANCE = [
        'forms' => 'Completing the application form',
        'documents' => 'Checking my documents for completeness',
        'appointment' => 'Booking a visa appointment',
        'other' => 'Something else / not sure',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $e) => $e->reference ??= 'VE-'.strtoupper(Str::random(8)));
    }

    public function assistanceLabels(): array
    {
        return array_values(array_intersect_key(self::ASSISTANCE, array_flip($this->assistance ?? [])));
    }
}
