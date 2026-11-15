<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\VisaEnquiry;
use Illuminate\Support\Carbon;

/**
 * Finds the customer behind a booking or request — same email, or same phone when
 * an email is missing — and creates one only when nobody matches (§4, AT-02). A match only
 * fills the customer's empty fields; it never overwrites what staff entered, and
 * never turns marketing off or touches Do Not Contact.
 */
class Customers
{
    public static function forBooking(Booking $booking): Customer
    {
        return self::match([
            'first_name' => $booking->first_name,
            'last_name' => $booking->last_name,
            'email' => $booking->email,
            'phone' => $booking->phone,
            'whatsapp' => $booking->whatsapp,
            'date_of_birth' => $booking->date_of_birth,
        ], $booking->source === 'referral' ? 'referral' : 'website', $booking->marketing_consent ? 'Booking '.$booking->reference : null, $booking->created_at);
    }

    public static function forVisaEnquiry(VisaEnquiry $enquiry): Customer
    {
        [$first, $last] = array_pad(preg_split('/\s+/', trim((string) $enquiry->full_name), 2), 2, null);

        return self::match(['first_name' => $first ?: 'Guest', 'last_name' => $last, 'email' => $enquiry->email, 'phone' => $enquiry->phone], 'visa', null, $enquiry->created_at);
    }

    /**
     * @param  array  $contact  first_name, last_name, email, phone, whatsapp, date_of_birth
     * @param  string|null  $consentSource  set when this contact agreed to marketing, e.g. "Booking DT-…"
     */
    public static function match(array $contact, string $source, ?string $consentSource = null, ?Carbon $at = null): Customer
    {
        $at ??= now();
        $email = mb_strtolower(trim((string) ($contact['email'] ?? '')));
        $digits = Customer::phoneDigits($contact['phone'] ?? null) ?? Customer::phoneDigits($contact['whatsapp'] ?? null);

        // Same email = same person. Same phone only counts when one side has no email: two
        // different emails on one phone may be a couple or family, so staff merge those after review (§3).
        $customer = ($email !== '' ? Customer::where('email', $email)->oldest('id')->first() : null)
            ?? ($digits ? Customer::where('phone_digits', $digits)->when($email !== '', fn ($q) => $q->where(fn ($x) => $x->whereNull('email')->orWhere('email', '')))->oldest('id')->first() : null)
            ?? new Customer(['source' => $source]);

        foreach (['first_name', 'last_name', 'email', 'phone', 'whatsapp', 'date_of_birth'] as $field) {
            if (blank($customer->{$field}) && filled($contact[$field] ?? null)) {
                $customer->{$field} = $contact[$field];
            }
        }
        $customer->first_name = $customer->first_name ?: 'Guest';
        if ($consentSource && !$customer->marketing_opt_in) {
            $customer->forceFill(['marketing_opt_in' => true, 'marketing_opt_in_at' => $at, 'marketing_opt_in_source' => $consentSource]);
        }
        if (!$customer->last_activity_at || $customer->last_activity_at->lt($at)) {
            $customer->last_activity_at = $at;
        }
        $customer->save();

        return $customer;
    }
}
