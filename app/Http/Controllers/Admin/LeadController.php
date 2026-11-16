<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerTag;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\LeadStage;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Models\User;
use App\Support\Activity;
use App\Support\Customers;
use App\Support\Leads;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Lead pipeline (§3): board and list, lead page with notes and quick actions, manual leads, conversion to booking. */
class LeadController extends Controller
{
    /** Closed columns on the board show this many recent leads; the list shows them all. */
    private const CLOSED_ON_BOARD = 8;

    public function __construct()
    {
        $this->middleware('permission:list-leads')->only(['index', 'show']);
        $this->middleware('permission:create-leads')->only(['create', 'store']);
        $this->middleware('permission:edit-leads')->only(['update', 'stage', 'assign', 'followUp', 'note', 'convert', 'tags']);
        $this->middleware('permission:delete-leads')->only(['destroy']);
    }

    public function index(Request $r): View|\Symfony\Component\HttpFoundation\Response
    {
        $query = $this->filtered($r)->with(['stage', 'assignedUser', 'tour.translations', 'departure']);
        if ($format = $r->query('export')) {
            abort_unless($r->user()->can('export-leads'), 403);
            abort_unless(in_array($format, \App\Support\TableExport::FORMATS, true), 404);

            return $this->export($r, $query, $format);
        }
        $stages = LeadStage::active()->get();
        $view = $r->input('view') === 'list' ? 'list' : 'board';

        return view('admin.leads.index', [
            'view' => $view,
            'stages' => $stages,
            'board' => $view === 'board' ? $stages->mapWithKeys(fn (LeadStage $stage) => [$stage->id => (clone $query)->where('lead_stage_id', $stage->id)
                ->when($stage->isClosed(), fn ($q) => $q->latest('updated_at')->limit(self::CLOSED_ON_BOARD), fn ($q) => $q->orderByRaw('next_follow_up_at is null')->orderBy('next_follow_up_at')->latest('id'))->get()]) : null,
            'counts' => (clone $this->filtered($r))->reorder()->selectRaw('lead_stage_id, count(*) n')->groupBy('lead_stage_id')->pluck('n', 'lead_stage_id'),
            'leads' => $view === 'list' ? $query->latest('id')->paginate(30)->withQueryString() : null,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'tours' => Tour::withTranslation()->get()->sortBy('name'),
            'campaigns' => Lead::whereNotNull('utm_campaign')->distinct()->orderBy('utm_campaign')->pluck('utm_campaign'),
            'due' => Lead::followUpDue()->count(),
        ]);
    }

    public function show(Lead $lead): View
    {
        $lead->load(['stage', 'customer', 'assignedUser', 'booking', 'requestBooking', 'visaEnquiry', 'tour.translations', 'departure', 'pickupPoint.translations', 'notes.user', 'tags']);

        return view('admin.leads.show', [
            'lead' => $lead,
            'stages' => LeadStage::active()->get(),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'tours' => Tour::withTranslation()->get()->sortBy('name'),
            'tags' => CustomerTag::active()->get()->merge($lead->tags)->unique('id'),
            'departures' => TourDeparture::with('tour.translations')->where('departs_at', '>=', now())->when($lead->tour_id, fn ($q) => $q->orderByRaw('tour_id = ? desc', [$lead->tour_id]))->orderBy('departs_at')->limit(200)->get(),
            'activity' => \App\Models\ActivityLog::where(['subject_type' => Lead::class, 'subject_id' => $lead->id])->whereNotIn('action', ['note_added', 'contact_logged'])->latest('id')->limit(30)->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.leads.form', ['lead' => new Lead(['source' => 'phone', 'type' => 'manual']), 'tours' => Tour::withTranslation()->get()->sortBy('name'), 'users' => User::orderBy('name')->get(['id', 'name'])]);
    }

    /** A lead taken by phone, walk-in, email… The customer is found or created like for website requests. */
    public function store(Request $r): RedirectResponse
    {
        $d = $this->validated($r);
        $customer = Customers::match(['first_name' => $d['first_name'], 'last_name' => $d['last_name'] ?? null, 'email' => $d['email'] ?? null, 'phone' => $d['phone'] ?? null, 'whatsapp' => $d['whatsapp'] ?? null], 'manual');
        $lead = Lead::create($d + ['lead_stage_id' => LeadStage::byKey('new')->id, 'customer_id' => $customer->id, 'type' => 'manual']);
        if (!empty($d['assigned_user_id'])) {
            Leads::assign($lead, User::find($d['assigned_user_id']));
        }

        return redirect()->route('leads.show', $lead)->with('message', 'Lead created.');
    }

    public function update(Request $r, Lead $lead): RedirectResponse
    {
        $lead->update(collect($this->validated($r))->except('assigned_user_id')->all());

        return back()->with('message', 'Lead details saved.');
    }

    /** Move to another stage — from the stage buttons or by dragging a card on the board (JSON). */
    public function stage(Request $r, Lead $lead): RedirectResponse|JsonResponse
    {
        $d = $r->validate(['lead_stage_id' => ['required', Rule::exists('lead_stages', 'id')->whereNull('archived_at')], 'lost_reason' => ['nullable', 'string', 'max:255']]);
        $stage = LeadStage::findOrFail($d['lead_stage_id']);
        if ($stage->kind === 'lost' && blank($d['lost_reason'] ?? null)) {
            $message = 'Please give a reason for the lost lead.';

            return $r->expectsJson() ? response()->json(['message' => $message, 'needs_reason' => true], 422) : back()->withErrors(['lost_reason' => $message]);
        }
        Leads::moveTo($lead, $stage, $d['lost_reason'] ?? null);

        return $r->expectsJson() ? response()->json(['stage' => $stage->name]) : back()->with('message', "Moved to “{$stage->name}”.");
    }

    public function assign(Request $r, Lead $lead): RedirectResponse
    {
        $d = $r->validate(['assigned_user_id' => ['nullable', 'exists:users,id']]);
        Leads::assign($lead, isset($d['assigned_user_id']) ? User::find($d['assigned_user_id']) : null);

        return back()->with('message', $lead->assigned_user_id ? 'Assigned to '.$lead->assignedUser->name.'.' : 'Unassigned.');
    }

    public function followUp(Request $r, Lead $lead): RedirectResponse
    {
        $d = $r->validate(['when' => ['nullable', Rule::in(['today', 'tomorrow', '3days', 'week', 'custom', 'clear'])], 'next_follow_up_at' => ['nullable', 'required_if:when,custom', 'date'], 'follow_up_note' => ['nullable', 'string', 'max:255']]);
        $tz = ActivityLogController::DISPLAY_TIMEZONE;
        $at = match ($d['when'] ?? 'custom') {
            'today' => now($tz)->setTime(17, 0), 'tomorrow' => now($tz)->addDay()->setTime(10, 0), '3days' => now($tz)->addDays(3)->setTime(10, 0), 'week' => now($tz)->addWeek()->setTime(10, 0),
            'clear' => null, default => isset($d['next_follow_up_at']) ? Carbon::parse($d['next_follow_up_at'], $tz) : null,
        };
        $lead->update(['next_follow_up_at' => $at?->utc(), 'follow_up_note' => $at ? ($d['follow_up_note'] ?? null) : null]);

        return back()->with('message', $at ? 'Reminder set for '.$at->format('D, M j g:i A').'.' : 'Reminder cleared.');
    }

    public function note(Request $r, Lead $lead): RedirectResponse
    {
        $d = $r->validate(['kind' => ['required', Rule::in(array_keys(LeadNote::KINDS))], 'outcome' => ['nullable', Rule::in(array_keys(LeadNote::OUTCOMES))], 'body' => ['nullable', 'required_if:kind,note', 'string', 'max:5000']], ['body.required_if' => 'Write the note.']);
        $note = Leads::addNote($lead, $r->user(), $d['kind'], $d['outcome'] ?? null, $d['body'] ?? null);

        return back()->with('message', $note->isContactAttempt() ? LeadNote::KINDS[$note->kind][0].' logged.' : 'Note added.');
    }

    public function convert(Request $r, Lead $lead): RedirectResponse
    {
        abort_if($lead->booking_id, 409, 'This lead already has a booking.');
        $d = $r->validate(['tour_departure_id' => ['required', 'exists:tour_departures,id'], 'guests' => ['required', 'integer', 'min:1', 'max:99']]);
        $departure = TourDeparture::with('tour')->findOrFail($d['tour_departure_id']);
        if ($departure->available_seats < $d['guests'] && !$r->boolean('allow_over')) {
            return back()->withInput()->withErrors(['guests' => "Only {$departure->available_seats} seats are left on this departure."]);
        }
        $booking = Leads::convert($lead, $departure, $d['guests']);

        return redirect()->route('bookings.show', $booking)->with('message', "Booking {$booking->reference} created from lead {$lead->reference}. Add the passengers and check the price.");
    }

    public function tags(Request $r, Lead $lead): RedirectResponse
    {
        $d = $r->validate(['tag_ids' => ['nullable', 'array'], 'tag_ids.*' => ['integer', 'exists:customer_tags,id']]);
        $before = $lead->tags()->pluck('name')->all();
        $lead->tags()->sync($d['tag_ids'] ?? []);
        $after = $lead->tags()->pluck('name')->all();
        if ($before !== $after) {
            Activity::record('tags_changed', $lead, ['tags' => [implode(', ', $before) ?: null, implode(', ', $after) ?: null]]);
        }

        return back()->with('message', 'Tags saved.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('leads.index')->with('message', "Lead {$lead->reference} deleted.");
    }

    private function validated(Request $r): array
    {
        return $r->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', 'required_without_all:phone,whatsapp'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'source' => ['required', Rule::in(array_keys(Lead::SOURCES))],
            'tour_id' => ['nullable', 'exists:tours,id'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:99'],
            'message' => ['nullable', 'string', 'max:5000'],
            'utm_campaign' => ['nullable', 'string', 'max:150'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
        ], ['email.required_without_all' => 'Enter an email, phone or WhatsApp number so the lead can be contacted.']);
    }

    /** The filtered list as CSV or Excel (CRM §25); logged like every export. */
    private function export(Request $r, Builder $query, string $format): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $tz = ActivityLogController::DISPLAY_TIMEZONE;
        $leads = $query->latest('id')->get();
        Activity::record('leads_exported', null, ['rows' => [null, (string) $leads->count()], 'format' => [null, strtoupper($format)], 'filters' => [null, http_build_query($r->except(['export', 'page', 'view'])) ?: 'none']], label: 'Lead list');

        return \App\Support\TableExport::download('dreamtour-leads-'.now($tz)->format('Y-m-d'), $leads, [
            'Reference' => fn ($l) => $l->reference,
            'Received' => fn ($l) => $l->created_at?->timezone($tz)->format('Y-m-d H:i'),
            'Stage' => fn ($l) => $l->stage?->name,
            'First name' => fn ($l) => $l->first_name,
            'Last name' => fn ($l) => $l->last_name,
            'Email' => fn ($l) => $l->email,
            'Phone' => fn ($l) => $l->phone,
            'WhatsApp' => fn ($l) => $l->whatsapp,
            'Tour' => fn ($l) => $l->tour?->name,
            'Trip date' => fn ($l) => $l->departure?->departs_at?->format('Y-m-d'),
            'Guests' => fn ($l) => $l->guests,
            'Source' => fn ($l) => $l->sourceLabel(),
            'Campaign' => fn ($l) => $l->utm_campaign,
            'Assigned to' => fn ($l) => $l->assignedUser?->name,
            'Last contacted' => fn ($l) => $l->last_contacted_at?->timezone($tz)->format('Y-m-d H:i'),
            'Next follow-up' => fn ($l) => $l->next_follow_up_at?->timezone($tz)->format('Y-m-d H:i'),
            'Lost reason' => fn ($l) => $l->lost_reason,
        ], $format);
    }

    private function filtered(Request $r): Builder
    {
        $d = $r->validate([
            'q' => ['nullable', 'string', 'max:100'], 'stage' => ['nullable', 'integer'], 'assigned' => ['nullable', 'string', 'max:20'],
            'source' => ['nullable', 'string', 'max:40'], 'type' => ['nullable', 'string', 'max:20'], 'campaign' => ['nullable', 'string', 'max:150'],
            'tour' => ['nullable', 'integer'], 'from' => ['nullable', 'date'], 'to' => ['nullable', 'date'], 'trip_from' => ['nullable', 'date'], 'trip_to' => ['nullable', 'date'],
            'contacted_from' => ['nullable', 'date'], 'contacted_to' => ['nullable', 'date'], 'due' => ['nullable', 'boolean'],
        ]);
        $tz = ActivityLogController::DISPLAY_TIMEZONE;
        $day = fn (string $date, bool $end = false) => ($end ? Carbon::parse($date, $tz)->endOfDay() : Carbon::parse($date, $tz)->startOfDay())->utc();
        $digits = strlen($x = preg_replace('/\D/', '', (string) ($d['q'] ?? ''))) >= 3 ? $x : null;

        return Lead::query()
            ->when($d['q'] ?? null, fn ($q, $term) => $q->where(fn ($x) => $x->whereRaw("concat_ws(' ', first_name, last_name) like ?", ["%{$term}%"])->orWhere('email', 'like', "%{$term}%")->orWhere('reference', $term)
                ->when($digits, fn ($y) => $y->orWhereRaw("regexp_replace(concat_ws('', phone, whatsapp), '[^0-9]', '') like ?", ["%{$digits}%"]))))
            ->when($d['stage'] ?? null, fn ($q, $id) => $q->where('lead_stage_id', $id))
            ->when($d['assigned'] ?? null, fn ($q, $who) => match ($who) { 'me' => $q->where('assigned_user_id', $r->user()->id), 'none' => $q->whereNull('assigned_user_id'), default => $q->where('assigned_user_id', (int) $who) })
            ->when($d['source'] ?? null, fn ($q, $source) => $q->where('source', $source))
            ->when($d['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($d['campaign'] ?? null, fn ($q, $campaign) => $q->where('utm_campaign', $campaign))
            ->when($d['tour'] ?? null, fn ($q, $id) => $q->where('tour_id', $id))
            ->when($d['from'] ?? null, fn ($q, $date) => $q->where('created_at', '>=', $day($date)))
            ->when($d['to'] ?? null, fn ($q, $date) => $q->where('created_at', '<=', $day($date, true)))
            ->when($d['trip_from'] ?? null, fn ($q, $date) => $q->whereHas('departure', fn ($x) => $x->where('departs_at', '>=', $day($date))))
            ->when($d['trip_to'] ?? null, fn ($q, $date) => $q->whereHas('departure', fn ($x) => $x->where('departs_at', '<=', $day($date, true))))
            ->when($d['contacted_from'] ?? null, fn ($q, $date) => $q->where('last_contacted_at', '>=', $day($date)))
            ->when($d['contacted_to'] ?? null, fn ($q, $date) => $q->where('last_contacted_at', '<=', $day($date, true)))
            ->when($r->boolean('due'), fn ($q) => $q->followUpDue());
    }
}
