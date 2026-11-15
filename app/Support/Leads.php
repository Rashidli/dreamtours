<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\LeadStage;
use App\Models\TourDeparture;
use App\Models\User;
use App\Models\VisaEnquiry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The lead workflow (§3): website requests become leads with their source and
 * campaign; assigning, logging contact and converting move them along the
 * pipeline (New → Assigned → Contacted → Awaiting deposit → Booking confirmed,
 * or Lost / Spam). Every move is recorded with stage names.
 */
class Leads
{
    /** A website booking or request (contact, call-back, private tour…). Never breaks the request itself. */
    public static function fromWebsiteBooking(Booking $booking): ?Lead
    {
        return self::capture(fn () => Lead::create(self::attribution() + [
            'lead_stage_id' => LeadStage::byKey('new')->id,
            'customer_id' => $booking->customer_id,
            'type' => $booking->tour_departure_id ? 'booking' : (array_key_exists($booking->type, Lead::TYPES) ? $booking->type : 'contact'),
            'request_booking_id' => $booking->id,
            'booking_id' => $booking->tour_departure_id ? $booking->id : null,
            'tour_id' => $booking->tour_id,
            'tour_departure_id' => $booking->tour_departure_id,
            'pickup_point_id' => $booking->pickup_point_id,
            'guests' => $booking->participant_count,
            'first_name' => $booking->first_name ?: 'Guest',
            'last_name' => $booking->last_name !== '-' ? $booking->last_name : null,
            'email' => $booking->email,
            'phone' => $booking->phone,
            'whatsapp' => $booking->whatsapp,
            'message' => trim(implode("\n\n", array_filter([$booking->comments, $booking->special_requests ? 'Special requests: '.$booking->special_requests : null]))) ?: null,
            'source' => 'website',
        ]), "booking {$booking->reference}");
    }

    public static function fromVisaEnquiry(VisaEnquiry $enquiry): ?Lead
    {
        [$first, $last] = array_pad(preg_split('/\s+/', trim((string) $enquiry->full_name), 2), 2, null);

        return self::capture(fn () => Lead::create(self::attribution() + [
            'lead_stage_id' => LeadStage::byKey('new')->id,
            'customer_id' => $enquiry->customer_id,
            'type' => 'visa',
            'visa_enquiry_id' => $enquiry->id,
            'guests' => $enquiry->applicants,
            'first_name' => $first ?: 'Guest',
            'last_name' => $last,
            'email' => $enquiry->email,
            'phone' => $enquiry->phone,
            'message' => trim("Visa support for {$enquiry->country}.\n".$enquiry->message),
            'source' => 'website',
        ]), "visa enquiry {$enquiry->reference}");
    }

    /** UTM tags, landing page and referrer remembered by RememberAttribution. */
    public static function attribution(): array
    {
        $saved = app()->bound('session') && request()->hasSession() ? (array) request()->session()->get('attribution', []) : [];

        return array_intersect_key($saved, array_flip(['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'landing_page', 'referrer']));
    }

    public static function moveTo(Lead $lead, LeadStage $stage, ?string $lostReason = null): void
    {
        $from = $lead->stage;
        if ($from?->is($stage)) {
            return;
        }
        $lead->forceFill(['lead_stage_id' => $stage->id, 'lost_reason' => $stage->kind === 'lost' ? $lostReason : null])->save();
        $lead->setRelation('stage', $stage);
        Activity::record('stage_changed', $lead, array_filter(['stage' => [$from?->name, $stage->name], 'lost reason' => $stage->kind === 'lost' ? [null, $lostReason] : null]));
    }

    /** Assigning a new lead moves it to "Assigned". */
    public static function assign(Lead $lead, ?User $user): void
    {
        $lead->update(['assigned_user_id' => $user?->id]);
        if ($user && $lead->stage?->key === 'new') {
            self::moveTo($lead, LeadStage::byKey('assigned'));
        }
    }

    /** A note, or a contact attempt that counts and moves New / Assigned leads to "Contacted". */
    public static function addNote(Lead $lead, User $user, string $kind, ?string $outcome, ?string $body): LeadNote
    {
        $note = $lead->notes()->create(['user_id' => $user->id, 'kind' => $kind, 'outcome' => $kind === 'note' ? null : $outcome, 'body' => $body]);
        if ($note->isContactAttempt()) {
            $lead->forceFill(['contact_attempts' => $lead->contact_attempts + 1, 'last_contacted_at' => now()])->saveQuietly();
            if (in_array($lead->stage?->key, ['new', 'assigned'], true)) {
                self::moveTo($lead, LeadStage::byKey('contacted'));
            }
        }
        Activity::record($kind === 'note' ? 'note_added' : 'contact_logged', $lead, array_filter(['type' => LeadNote::KINDS[$kind][0] ?? $kind, 'outcome' => LeadNote::OUTCOMES[$note->outcome] ?? null, 'note' => $body]));

        return $note;
    }

    /**
     * Turn a lead into a booking without re-typing anything: contact details, guests,
     * pickup and message are copied; the price starts from the departure's adult
     * fare and can be adjusted on the booking page.
     */
    public static function convert(Lead $lead, TourDeparture $departure, int $guests): Booking
    {
        return DB::transaction(function () use ($lead, $departure, $guests) {
            $fare = (float) ($departure->fares()->firstWhere('code', 'adult')?->price ?? $departure->price);
            $booking = Booking::create([
                'customer_id' => $lead->customer_id,
                'tour_id' => $departure->tour_id,
                'tour_departure_id' => $departure->id,
                'pickup_point_id' => $lead->pickup_point_id,
                'type' => 'tour',
                'status' => 'payment_pending',
                'payment_status' => 'unpaid',
                'first_name' => $lead->first_name,
                'last_name' => $lead->last_name ?: '-',
                'email' => $lead->email,
                'phone' => $lead->phone,
                'whatsapp' => $lead->whatsapp,
                'participant_count' => $guests,
                'comments' => $lead->message,
                'source' => 'lead',
                'currency' => $departure->currency ?: 'CAD',
                'tax_included' => (bool) $departure->tour?->tax_included,
                'total_amount' => $fare * $guests,
                'deposit_amount' => (float) $departure->tour?->deposit_per_person * $guests,
                'amount_paid' => 0,
            ]);
            $lead->update(['booking_id' => $booking->id, 'customer_id' => $lead->customer_id ?: $booking->customer_id, 'tour_id' => $departure->tour_id, 'tour_departure_id' => $departure->id, 'guests' => $guests]);
            self::moveTo($lead, LeadStage::byKey('awaiting_deposit'));
            Activity::record('converted', $lead, ['booking' => $booking->reference, 'departure' => $departure->activityLabel(), 'guests' => $guests]);

            return $booking;
        });
    }

    /** A booking set to Confirmed marks its lead "Booking confirmed". */
    public static function bookingConfirmed(Booking $booking): void
    {
        if ($booking->status !== 'confirmed') {
            return;
        }
        $confirmed = LeadStage::byKey('confirmed');
        Lead::where('booking_id', $booking->id)->where('lead_stage_id', '!=', $confirmed->id)->with('stage')->get()
            ->reject(fn (Lead $lead) => in_array($lead->stage?->kind, ['lost', 'spam'], true))
            ->each(fn (Lead $lead) => self::moveTo($lead, $confirmed));
    }

    private static function capture(callable $create, string $what): ?Lead
    {
        try {
            return $create();
        } catch (\Throwable $e) {
            Log::error('Lead could not be created', ['for' => $what, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
