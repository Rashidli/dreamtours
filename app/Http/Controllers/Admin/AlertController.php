<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\StaffAlert;
use App\Support\Alerts;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Staff alerts (CRM §15): open and done, each linked to its record. */
class AlertController extends Controller
{
    /** The list filters, shared by the list and "Mark all done". */
    private static function filterRules(): array
    {
        return ['type' => ['nullable', Rule::in(array_keys(Alerts::TYPES))], 'mine' => ['nullable', 'boolean']];
    }

    /** Alerts this person may see, narrowed by the list filters (kind, assigned to me). */
    private function filtered(Request $request, array $f): Builder
    {
        return StaffAlert::visibleTo($request->user())->when($f['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->when(filter_var($f['mine'] ?? false, FILTER_VALIDATE_BOOLEAN), fn ($q) => $q->where('assigned_user_id', $request->user()->id));
    }

    public function index(Request $request): View
    {
        Alerts::sweepIfDue();
        $f = $request->validate(['show' => ['nullable', Rule::in(['open', 'done'])]] + self::filterRules());
        $done = ($f['show'] ?? 'open') === 'done';

        return view('admin.alerts.index', [
            'alerts' => $this->filtered($request, $f)->when($done, fn ($q) => $q->whereNotNull('completed_at')->latest('completed_at'), fn ($q) => $q->open()->latest())
                ->with(['assignedUser', 'completedBy'])->paginate(30)->withQueryString(),
            'counts' => StaffAlert::visibleTo($request->user())->open()->selectRaw('type, count(*) n')->groupBy('type')->pluck('n', 'type'),
            'f' => ['show' => $done ? 'done' : 'open', 'type' => $f['type'] ?? null],
        ]);
    }

    /** The header bell's list, loaded when it is opened. */
    public function dropdown(Request $request): View
    {
        return view('admin.alerts._dropdown', ['alerts' => StaffAlert::visibleTo($request->user())->open()->latest()->limit(6)->get()]);
    }

    public function complete(Request $request, StaffAlert $alert): RedirectResponse
    {
        abort_unless(StaffAlert::visibleTo($request->user())->whereKey($alert->id)->exists(), 403);
        if (!$alert->completed_at) {
            $alert->forceFill(['completed_at' => now(), 'completed_by' => $request->user()->id, 'completed_note' => 'Marked done'])->save();
        }

        return $request->filled('go') && $alert->url ? redirect()->to($alert->url) : back()->with('message', 'Alert marked done.');
    }

    public function completeAll(Request $request): RedirectResponse
    {
        // Same filters as the list, so "Mark all done" closes exactly the open alerts the person is looking at.
        $n = $this->filtered($request, $request->validate(self::filterRules()))->open()
            ->update(['completed_at' => now(), 'completed_by' => $request->user()->id, 'completed_note' => 'Marked done', 'updated_at' => now()]);

        return back()->with('message', "$n ".str('alert')->plural($n).' marked done.');
    }

    /** Which alert types are raised at all; thresholds live in Settings › Staff alerts. */
    public function settings(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('edit-settings'), 403);
        $on = $request->validate(['on' => ['nullable', 'array'], 'on.*' => [Rule::in(array_keys(Alerts::TYPES))]])['on'] ?? [];
        Setting::set('alerts_off', implode(',', array_diff(array_keys(Alerts::TYPES), $on)));

        return back()->with('message', 'Alert settings saved.');
    }
}
