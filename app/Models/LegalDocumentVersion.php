<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** A frozen copy of a legal document's wording; never edited after it is created. */
class LegalDocumentVersion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['version', 'title', 'heading', 'body', 'created_by'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(LegalDocument::class, 'legal_document_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'booking_legal_acceptances')->withPivot('accepted_at');
    }
}
