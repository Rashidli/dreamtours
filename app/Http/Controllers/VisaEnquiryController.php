<?php

namespace App\Http\Controllers;

use App\Mail\VisaEnquiryReceived;
use App\Models\VisaEnquiry;
use App\Support\CountryCodes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** The Visa Support page's own enquiry form: no tour, departure, pickup or seats involved. */
class VisaEnquiryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'phone_country' => ['required', 'string', 'size:2'],
            'phone' => ['required', 'string', 'max:30'],
            'country' => ['required', 'string', 'max:100'],
            'passport_country' => ['nullable', 'string', 'max:100'],
            'assistance' => ['required', 'array', 'min:1'],
            'assistance.*' => [Rule::in(array_keys(VisaEnquiry::ASSISTANCE))],
            'applicants' => ['nullable', 'integer', 'min:1', 'max:50'],
            'travel_date' => ['nullable', 'date', 'after_or_equal:today'],
            'message' => ['nullable', 'string', 'max:3000'],
        ], [
            'assistance.required' => 'Please choose what you need help with.',
        ]);

        $phone = international_phone(CountryCodes::dial($data['phone_country']), $data['phone']);
        if (!$phone) {
            throw ValidationException::withMessages(['phone' => 'Please enter a complete phone or WhatsApp number for the selected country.']);
        }

        $enquiry = VisaEnquiry::create(['phone' => $phone, 'applicants' => $data['applicants'] ?? 1] + collect($data)->except(['phone', 'phone_country', 'applicants'])->all());

        if ($to = setting('visa_email') ?: setting('email')) {
            try {
                Mail::to($to)->send(new VisaEnquiryReceived($enquiry));
            } catch (\Throwable $e) {
                // The enquiry is saved; a mail outage must not lose it.
                Log::error('Visa enquiry email failed', ['reference' => $enquiry->reference, 'error' => $e->getMessage()]);
            }
        }

        return response()->json(['reference' => $enquiry->reference]);
    }
}
