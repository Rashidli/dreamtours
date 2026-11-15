<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DepartureStaff;
use App\Models\TourDeparture;
use App\Support\Activity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Assign or remove the leader, driver, guide or coordinator of one departure (CRM §5, §12). */
class DepartureStaffController extends Controller
{
    public function __construct() { $this->middleware('permission:assign-departure-staff'); }

    public function store(Request $request, TourDeparture $departure): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', Rule::in(array_keys(DepartureStaff::ROLES))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $assignment = DepartureStaff::firstOrNew(['tour_departure_id' => $departure->id, 'user_id' => $data['user_id'], 'role' => $data['role']]);
        $new = !$assignment->exists;
        $assignment->fill(['note' => $data['note'] ?? null] + ($new ? ['assigned_by' => $request->user()->id] : []))->save();
        $assignment->load('user');
        if ($new) {
            Activity::record('staff_assigned', $departure, ['staff' => [null, $assignment->user->name.' · '.$assignment->role_label]]);
        }

        return $this->back($departure, $new ? "{$assignment->user->name} assigned as {$assignment->role_label}." : 'Note updated.');
    }

    public function destroy(TourDeparture $departure, DepartureStaff $assignment): RedirectResponse
    {
        abort_unless($assignment->tour_departure_id === $departure->id, 404);
        $assignment->load('user');
        $assignment->delete();
        Activity::record('staff_removed', $departure, ['staff' => [$assignment->user?->name.' · '.$assignment->role_label, null]]);

        return $this->back($departure, ($assignment->user?->name ?? 'Staff member').' removed; they no longer see this trip.');
    }

    private function back(TourDeparture $departure, string $message): RedirectResponse
    {
        return redirect()->to(route('tours.edit', $departure->tour_id).'#departure-'.$departure->id)->with('message', $message);
    }
}
