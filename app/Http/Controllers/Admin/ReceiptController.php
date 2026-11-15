<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Support\Activity;
use App\Support\BookingMoney;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Branded receipts and booking statements (§7): one page per payment or refund,
 * and a statement with every transaction, the totals and the balance. Shown as a
 * printable page, or downloaded as PDF with ?pdf=1.
 */
class ReceiptController extends Controller
{
    public function __construct() { $this->middleware('permission:list-bookings'); }

    public function payment(Request $r, Booking $booking, BookingPayment $payment): Response
    {
        abort_unless($payment->booking_id === $booking->id, 404);
        abort_unless($payment->status === 'confirmed' && $payment->receipt_number, 404, 'Receipts exist for confirmed payments and refunds only.');

        return $this->render($r, 'admin.receipts.payment', ['booking' => $this->loaded($booking), 'payment' => $payment->load('refundOf'), 'summary' => BookingMoney::summary($booking)], $payment->receipt_number);
    }

    public function statement(Request $r, Booking $booking): Response
    {
        abort_unless($booking->tour_departure_id, 404);

        return $this->render($r, 'admin.receipts.statement', ['booking' => $this->loaded($booking), 'summary' => BookingMoney::summary($booking)], 'Statement-'.$booking->reference);
    }

    private function loaded(Booking $booking): Booking
    {
        return $booking->load(['tour.translations', 'departure', 'pickupPoint.translations', 'passengers', 'payments.refundOf', 'adjustments.passenger']);
    }

    private function render(Request $r, string $view, array $data, string $name): Response
    {
        $html = view($view, $data + ['forPdf' => $r->boolean('pdf')])->render();
        if (!$r->boolean('pdf')) {
            return response($html);
        }
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans'); // covers – · é and other non-ASCII characters
        $options->set('isRemoteEnabled', false);
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html);
        $pdf->setPaper('letter');
        $pdf->render();
        Activity::record('pdf_downloaded', $data['booking'], ['document' => $name]);

        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$name.'.pdf"']);
    }
}
