<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DepartureVehicle;
use App\Models\TourDeparture;
use App\Models\VehicleLayout;
use App\Support\Activity;
use App\Support\Seating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * The seating board of one departure (CRM §9). The page loads once; every action posts JSON and
 * gets the whole board back, so what staff see is always what was saved.
 */
class SeatingController extends Controller
{
    public function show(Request $request, TourDeparture $departure): View
    {
        abort_unless(Seating::canView($request->user(), $departure), 403, 'This trip is not assigned to you.');
        $departure->load(['tour.translations', 'staff.user']);

        return view('admin.seating.board', [
            'departure' => $departure,
            'state' => Seating::state($departure, $request->user()),
            'templates' => Seating::canEdit($request->user()) ? VehicleLayout::where('is_active', true)->orderBy('name')->get() : collect(),
        ]);
    }

    public function place(Request $request, TourDeparture $departure): JsonResponse
    {
        $this->authorizeEdit($request);
        $d = $request->validate([
            'placements' => ['required', 'array', 'min:1', 'max:200'],
            'placements.*.p' => ['required', 'integer'],
            'placements.*.v' => ['nullable', 'integer'],
            'placements.*.s' => ['nullable', 'string', 'max:10'],
        ]);

        return $this->respond($request, $departure, fn () => Seating::place($departure, array_map(fn ($x) => $x + ['v' => null, 's' => null], $d['placements']), $request->user()));
    }

    public function block(Request $request, TourDeparture $departure): JsonResponse
    {
        $this->authorizeEdit($request);
        $d = $request->validate(['vehicle' => ['required', 'integer'], 'seat' => ['required', 'string', 'max:10'], 'blocked' => ['required', 'boolean']]);
        $vehicle = $departure->vehicles()->findOrFail($d['vehicle']);

        return $this->respond($request, $departure, fn () => Seating::block($vehicle, $d['seat'], $d['blocked']));
    }

    public function autofill(Request $request, TourDeparture $departure): JsonResponse
    {
        $this->authorizeEdit($request);
        $d = $request->validate(['vehicle' => ['nullable', 'integer'], 'stop' => ['nullable', 'integer']]);

        return $this->respond($request, $departure, function () use ($departure, $d, $request) {
            $n = Seating::autofill($departure, $d['vehicle'] ?? null, array_key_exists('stop', $d) && $d['stop'] !== null ? (int) $d['stop'] : null, $request->user());

            return $n ? "$n ".str('passenger')->plural($n).' seated.' : 'Nobody left to seat, or no free seats.';
        });
    }

    public function storeVehicle(Request $request, TourDeparture $departure): RedirectResponse
    {
        $this->authorizeEdit($request);
        $d = $request->validate(['vehicle_layout_id' => ['required', 'exists:vehicle_layouts,id'], 'label' => ['nullable', 'string', 'max:50'], 'note' => ['nullable', 'string', 'max:255']]);
        $vehicle = Seating::addVehicle($departure, VehicleLayout::findOrFail($d['vehicle_layout_id']), $d['label'] ?? null, $d['note'] ?? null);
        $seats = count($vehicle->seatLabels());
        $warning = $departure->vehicles()->get()->sum(fn ($v) => count($v->openSeatLabels())) < $departure->bookedSeats() ? ' The buses have fewer seats than booked passengers.' : '';

        return redirect()->route('seating.show', $departure)->with('message', "{$vehicle->label} added with {$seats} seats.".$warning);
    }

    public function updateVehicle(Request $request, TourDeparture $departure, DepartureVehicle $vehicle): RedirectResponse
    {
        $this->authorizeEdit($request);
        abort_unless($vehicle->tour_departure_id === $departure->id, 404);
        $vehicle->update($request->validate(['label' => ['required', 'string', 'max:50'], 'note' => ['nullable', 'string', 'max:255']]));

        return redirect()->route('seating.show', $departure)->with('message', "{$vehicle->label} saved.");
    }

    /** Only an empty bus can be removed, so nobody silently loses a seat. */
    public function destroyVehicle(Request $request, TourDeparture $departure, DepartureVehicle $vehicle): RedirectResponse
    {
        $this->authorizeEdit($request);
        abort_unless($vehicle->tour_departure_id === $departure->id, 404);
        $seated = $vehicle->passengers()->whereHas('booking', fn ($q) => $q->where('status', '!=', 'cancelled'))->count();
        if ($seated) {
            return redirect()->route('seating.show', $departure)->withErrors(['vehicle' => "{$seated} passengers are seated on {$vehicle->label}. Move or unseat them first."]);
        }
        $vehicle->passengers()->update(['departure_vehicle_id' => null, 'seat_number' => null]); // cancelled bookings only
        $vehicle->delete();
        Activity::record('vehicle_removed', $departure, ['vehicle' => [$vehicle->label, null]]);

        return redirect()->route('seating.show', $departure)->with('message', "{$vehicle->label} removed.");
    }

    /** Final seating: marks every bus published and, if asked, emails seat numbers that are new or changed. */
    public function publish(Request $request, TourDeparture $departure): RedirectResponse
    {
        $this->authorizeEdit($request);
        abort_unless($departure->vehicles()->exists(), 422, 'Add a bus first.');
        $r = Seating::publish($departure, $request->user(), $request->boolean('notify'));
        $message = 'Seating published.'.($request->boolean('notify')
            ? ' Seat numbers emailed to '.$r['sent'].' '.str('booking')->plural($r['sent']).'.'.($r['waiting'] ? " {$r['waiting']} will get theirs once fully paid and seated." : '')
            : ' No emails were sent.');

        return redirect()->route('seating.show', $departure)->with('message', $message);
    }

    /** Seat chart as Excel (one row per passenger) or PDF (each bus drawn with names). */
    public function export(Request $request, TourDeparture $departure, string $format): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless(Seating::canView($request->user(), $departure), 403);
        abort_unless(in_array($format, ['xlsx', 'pdf'], true), 404);
        $departure->load(['tour.translations', 'vehicles', 'staff.user', 'staff.vehicle']);
        $passengers = Seating::passengers($departure);
        $name = 'seating-'.str($departure->tour?->name)->slug()->limit(40, '').'-'.$departure->departs_at->format('Y-m-d');
        Activity::record('seating_exported', $departure, ['format' => [null, strtoupper($format)]]);

        if ($format === 'xlsx') {
            $order = $departure->vehicles->flatMap(fn ($v) => collect($v->seatLabels())->map(fn ($s) => $v->id.'|'.$s))->flip();
            $rows = $passengers->sortBy(fn ($p) => $p->departure_vehicle_id ? $order[$p->departure_vehicle_id.'|'.$p->seat_number] ?? PHP_INT_MAX : PHP_INT_MAX)->values();

            return response()->streamDownload(function () use ($rows) {
                $writer = new \OpenSpout\Writer\XLSX\Writer();
                $writer->openToFile('php://output');
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Bus', 'Seat', 'Passenger', 'Category', 'Pickup', 'Booking', 'Meal', 'Phone']));
                foreach ($rows as $p) {
                    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([$p->departureVehicle?->label ?? 'No seat', (string) $p->seat_number, trim("{$p->first_name} {$p->last_name}"), $p->category !== 'adult' ? (string) $p->category : '', (string) $p->pickupPoint?->name, $p->booking->reference, (string) $p->meal_selection, (string) ($p->phone ?: $p->booking->phone)]));
                }
                $writer->close();
            }, "$name.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
        }

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $pdf = new \Dompdf\Dompdf($options);
        $pdf->loadHtml(view('admin.seating.pdf', ['departure' => $departure, 'seated' => $passengers->filter(fn ($p) => $p->departure_vehicle_id)->keyBy(fn ($p) => $p->departure_vehicle_id.'|'.$p->seat_number), 'unseated' => $passengers->reject(fn ($p) => $p->departure_vehicle_id)])->render());
        $pdf->setPaper('letter');
        $pdf->render();

        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.$name.'.pdf"']);
    }

    private function authorizeEdit(Request $request): void
    {
        abort_unless(Seating::canEdit($request->user()), 403);
    }

    /** Run an action and answer with the fresh board; a refused action returns 422 with the board too. */
    private function respond(Request $request, TourDeparture $departure, callable $action): JsonResponse
    {
        try {
            $result = $action();
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first(), 'state' => Seating::state($departure, $request->user())], 422);
        }

        return response()->json(['message' => is_string($result) ? $result : null, 'state' => Seating::state($departure, $request->user())]);
    }
}
