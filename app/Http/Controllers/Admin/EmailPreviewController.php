<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingConfirmed;
use App\Mail\BookingReceived;
use App\Mail\EnquiryReceived;
use App\Mail\SeatAssignment;
use App\Mail\VisaEnquiryReceived;
use App\Models\Booking;
use App\Models\VisaEnquiry;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** Shows each automatic email as the recipient sees it, filled with the latest real record. Nothing is sent. */
class EmailPreviewController extends Controller
{
    private const RELATIONS = ['tour.translations', 'departure', 'pickupPoint.translations', 'passengers.pickupPoint.translations', 'tour.contentItems.translations'];

    public function __construct() { $this->middleware('permission:list-bookings'); }

    public function index(): View
    {
        return view('admin.email_previews.index', ['emails' => collect($this->emails())->map(fn ($email) => ['title' => $email[0], 'to' => $email[1], 'available' => (bool) $email[2]()])]);
    }

    public function show(string $key): Response
    {
        $email = $this->emails()[$key] ?? abort(404);
        $mail = $email[2]() ?? abort(404, 'There is no record to fill this email with yet.');

        return response($mail->render());
    }

    /** key => [title, recipient, fn(): ?Mailable] */
    private function emails(): array
    {
        $booking = fn () => Booking::whereNotNull('tour_departure_id')->with(self::RELATIONS)->latest('id')->first();
        $enquiry = fn () => Booking::whereNull('tour_departure_id')->whereIn('type', array_keys(EnquiryReceived::LABELS))->with('tour.translations')->latest('id')->first();

        return [
            'booking-received' => ['Booking request received', 'Customer', fn () => ($b = $booking()) ? new BookingReceived($b) : null],
            'booking-received-office' => ['New booking request', 'Office', fn () => ($b = $booking()) ? new BookingReceived($b, forAdmin: true) : null],
            'booking-confirmed' => ['Booking confirmed', 'Customer', fn () => ($b = $booking()) ? new BookingConfirmed($b) : null],
            'enquiry-received' => ['Request received (contact, call-back, private tour)', 'Customer', fn () => ($b = $enquiry()) ? new EnquiryReceived($b) : null],
            'enquiry-received-office' => ['New request (contact, call-back, private tour)', 'Office', fn () => ($b = $enquiry()) ? new EnquiryReceived($b, forAdmin: true) : null],
            'seat-assignment' => ['Seat numbers', 'Customer', fn () => ($b = $booking()) ? new SeatAssignment($b) : null],
            'visa-enquiry' => ['New visa enquiry', 'Visa email', fn () => ($v = VisaEnquiry::latest('id')->first()) ? new VisaEnquiryReceived($v) : null],
        ];
    }
}
