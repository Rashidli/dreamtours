<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\StaffAlert;
use App\Support\Alerts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Staff alerts (CRM §15): open and done, each linked to its record. */
class AlertController extends Controller
{
    public function index(Request $request): View
    {
        Alerts::sweepIfDue();
        $f = $request->validate(['show' => ['nullable', Rule::in(['open', 'done'])], 'type' => ['nullable', Rule::in(array_keys(Alerts::TYPES))], 'mine' => ['nullable', 'boolean']]);
        $done = ($f['show'] ?? 'open') === 'done';
        $base = fn () => StaffAlert::visibleTo($request->user())->when($f['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->when($request->boolean('mine'), fn ($q) => $q->where('assigned_user_id', $request->user()->id));

        return view('admin.alerts.index', [
            'alerts' => $base()->when($done, fn ($q) => $q->whereNotNull('completed_at')->latest('completed_at'), fn ($q) => $q->open()->latest())
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
        $d = $request->validate(['type' => ['nullable', Rule::in(array_keys(Alerts::TYPES))]]);
        $n = StaffAlert::visibleTo($request->user())->open()->when($d['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
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
