<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingPassenger;
use App\Models\Customer;
use App\Models\CustomerStatus;
use App\Models\CustomerTag;
use App\Models\Destination;
use App\Models\User;
use App\Support\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Customer directory (§4): list, profile with full history, create, edit, archive and CSV export. */
class CustomerController extends Controller
{
    public const SORTS = ['recent' => 'Last activity', 'name' => 'Name (A–Z)', 'newest' => 'Signed up (newest)', 'oldest' => 'Signed up (oldest)', 'spent' => 'Total paid (highest)', 'trips' => 'Trips (most)', 'outstanding' => 'Outstanding (highest)'];

    public function __construct()
    {
        $this->middleware('permission:list-customers')->only(['index', 'show']);
        $this->middleware('permission:create-customers')->only(['create', 'store']);
        $this->middleware('permission:edit-customers')->only(['edit', 'update']);
        $this->middleware('permission:delete-customers')->only(['destroy', 'restore']);
    }

    public function index(Request $r): View|StreamedResponse
    {
        $query = $this->filtered($r);
        if ($r->input('export') === 'csv') {
            abort_unless($r->user()->can('export-customers'), 403);

            return $this->export($r, $query);
        }

        return view('admin.customers.index', [
            'customers' => $query->with(['status', 'tags'])->paginate(30)->withQueryString(),
            'statuses' => CustomerStatus::orderBy('sort_order')->get(),
            'tags' => CustomerTag::orderBy('name')->get(),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'destinations' => Destination::withTranslation()->get()->sortBy('name'),
            'sorts' => self::SORTS,
        ]);
    }

    public function show(Customer $customer): View
    {
        $customer->load(['status', 'tags', 'assignedUser', 'bookings.tour.translations', 'bookings.departure', 'bookings.passengers', 'visaEnquiries']);
        $stats = Customer::withTrashed()->withStats()->whereKey($customer->id)->first();
        $bookingIds = $customer->bookings->pluck('id');

        return view('admin.customers.show', [
            'customer' => $customer,
            'stats' => $stats,
            // Everyone who travelled on this customer's bookings, except the customer themselves.
            'companions' => BookingPassenger::whereIn('booking_id', $bookingIds)->get()
                ->reject(fn ($p) => mb_strtolower(trim("{$p->first_name} {$p->last_name}")) === mb_strtolower($customer->full_name))
                ->unique(fn ($p) => mb_strtolower(trim("{$p->first_name} {$p->last_name}")))->values(),
            'activity' => \App\Models\ActivityLog::where(fn ($q) => $q->where(['subject_type' => Customer::class, 'subject_id' => $customer->id])
                ->orWhere(fn ($x) => $x->where('subject_type', Booking::class)->whereIn('subject_id', $bookingIds)))
                ->latest('id')->limit(100)->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Customer(['source' => 'manual', 'customer_status_id' => CustomerStatus::where('name', 'New')->value('id')]));
    }

    public function store(Request $r): RedirectResponse
    {
        $customer = new Customer();
        $this->save($r, $customer);

        return redirect()->route('customers.show', $customer)->with('message', 'Customer created.');
    }

    public function edit(Customer $customer): View
    {
        return $this->form($customer);
    }

    public function update(Request $r, Customer $customer): RedirectResponse
    {
        $this->save($r, $customer);

        return redirect()->route('customers.show', $customer)->with('message', 'Customer updated.');
    }

    /** Archive (soft delete): bookings and history stay; the customer can be restored. */
    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('message', 'Customer archived. Their bookings and history are kept.');
    }

    public function restore(int $customer): RedirectResponse
    {
        $model = Customer::onlyTrashed()->findOrFail($customer);
        $model->restore();

        return redirect()->route('customers.show', $model)->with('message', 'Customer restored.');
    }

    private function form(Customer $customer): View
    {
        return view('admin.customers.form', [
            'customer' => $customer,
            'statuses' => CustomerStatus::active()->get()->when($customer->status?->archived_at, fn ($all) => $all->push($customer->status)),
            'tags' => CustomerTag::active()->get()->merge($customer->tags()->whereNotNull('archived_at')->get()),
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function save(Request $r, Customer $customer): void
    {
        $canSeeBirthdays = $r->user()->can('view-customer-birthdays');
        $d = $r->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'preferred_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer->id)->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:50', 'required_without:email'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'city' => ['nullable', 'string', 'max:100'],
            'language' => ['nullable', 'string', 'max:30'],
            'customer_status_id' => ['nullable', 'exists:customer_statuses,id'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
            'source' => ['nullable', Rule::in(array_keys(Customer::SOURCES))],
            'marketing_opt_in' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:customer_tags,id'],
        ], ['email.unique' => 'Another customer already has this email. Open that customer instead of creating a duplicate.']);

        if (!$canSeeBirthdays) {
            unset($d['date_of_birth']); // hidden from this user, so never cleared by them either
        }
        $optIn = $r->boolean('marketing_opt_in');
        if ($optIn !== (bool) $customer->marketing_opt_in) {
            // Consent record: when and by whom it was given or withdrawn (§19, §24).
            $d['marketing_opt_in_at'] = now();
            $d['marketing_opt_in_source'] = ($optIn ? 'Recorded by ' : 'Withdrawn by ').$r->user()->name;
        }
        $d['marketing_opt_in'] = $optIn;
        if (!$customer->exists) {
            $d['last_activity_at'] = now();
            $d['source'] = ($d['source'] ?? null) ?: 'manual';
        }
        $customer->fill(collect($d)->except('tag_ids')->all())->save();

        $before = $customer->tags()->pluck('name')->all();
        $customer->tags()->sync($d['tag_ids'] ?? []);
        $after = $customer->tags()->pluck('name')->all();
        if ($before !== $after) {
            Activity::record('tags_changed', $customer, ['tags' => [implode(', ', $before) ?: null, implode(', ', $after) ?: null]]);
        }
    }

    private function filtered(Request $r): Builder
    {
        $d = $r->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'integer'],
            'tag' => ['nullable', 'integer'],
            'source' => ['nullable', Rule::in(array_keys(Customer::SOURCES))],
            'assigned' => ['nullable', 'integer'],
            'marketing' => ['nullable', Rule::in(['eligible', 'not_eligible'])],
            'birthday_month' => ['nullable', 'integer', 'between:1,12'],
            'signup_from' => ['nullable', 'date'], 'signup_to' => ['nullable', 'date'],
            'trip_from' => ['nullable', 'date'], 'trip_to' => ['nullable', 'date'],
            'min_trips' => ['nullable', 'integer', 'min:0'],
            'min_spent' => ['nullable', 'numeric', 'min:0'],
            'destination' => ['nullable', 'integer'],
            'outstanding' => ['nullable', 'boolean'],
            'archived' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(array_keys(self::SORTS))],
        ]);
        // Partial numbers work too: "647 555" finds 647-555-0000.
        $phone = strlen($digits = preg_replace('/\D/', '', (string) ($d['q'] ?? ''))) >= 3 ? $digits : null;
        $query = Customer::query()->select('customers.*')->withStats()
            ->when($r->boolean('archived'), fn ($q) => $q->onlyTrashed())
            ->when($d['q'] ?? null, fn ($q, $term) => $q->where(fn ($x) => $x->whereRaw("concat_ws(' ', first_name, last_name, preferred_name) like ?", ["%{$term}%"])->orWhere('email', 'like', '%'.mb_strtolower($term).'%')->when($phone, fn ($y) => $y->orWhere('phone_digits', 'like', "%{$phone}%"))))
            ->when($d['status'] ?? null, fn ($q, $id) => $q->where('customer_status_id', $id))
            ->when($d['tag'] ?? null, fn ($q, $id) => $q->whereHas('tags', fn ($x) => $x->whereKey($id)))
            ->when($d['source'] ?? null, fn ($q, $source) => $q->where('source', $source))
            ->when($d['assigned'] ?? null, fn ($q, $id) => $q->where('assigned_user_id', $id))
            ->when(($d['marketing'] ?? null) === 'eligible', fn ($q) => $q->marketingEligible())
            ->when(($d['marketing'] ?? null) === 'not_eligible', fn ($q) => $q->whereNotIn('customers.id', Customer::marketingEligible()->select('id')))
            ->when(($d['birthday_month'] ?? null) && $r->user()->can('view-customer-birthdays'), fn ($q) => $q->whereMonth('date_of_birth', $d['birthday_month']))
            ->when($d['signup_from'] ?? null, fn ($q, $date) => $q->whereDate('customers.created_at', '>=', $date))
            ->when($d['signup_to'] ?? null, fn ($q, $date) => $q->whereDate('customers.created_at', '<=', $date))
            ->when($d['trip_from'] ?? null, fn ($q, $date) => $q->having('last_trip_at', '>=', $date))
            ->when($d['trip_to'] ?? null, fn ($q, $date) => $q->having('last_trip_at', '<=', $date.' 23:59:59'))
            ->when(isset($d['min_trips']), fn ($q) => $q->having('trips_count', '>=', $d['min_trips']))
            ->when(isset($d['min_spent']), fn ($q) => $q->having('total_paid', '>=', $d['min_spent']))
            ->when($r->boolean('outstanding'), fn ($q) => $q->having('outstanding', '>', 0))
            ->when($d['destination'] ?? null, fn ($q, $id) => $q->whereHas('bookings.tour', fn ($x) => $x->where('destination_id', $id)));

        return match ($d['sort'] ?? 'recent') {
            'name' => $query->orderBy('first_name')->orderBy('last_name'),
            'newest' => $query->latest('customers.created_at'),
            'oldest' => $query->oldest('customers.created_at'),
            'spent' => $query->orderByDesc('total_paid'),
            'trips' => $query->orderByDesc('trips_count'),
            'outstanding' => $query->orderByDesc('outstanding'),
            default => $query->orderByDesc('last_activity_at')->orderByDesc('customers.id'),
        };
    }

    private function export(Request $r, Builder $query): StreamedResponse
    {
        $birthdays = $r->user()->can('view-customer-birthdays');
        $customers = $query->with(['status', 'tags', 'assignedUser'])->get();
        Activity::record('customers_exported', null, ['rows' => $customers->count(), 'filters' => http_build_query($r->except(['export', 'page'])) ?: 'none'], label: 'Customer list');

        return response()->streamDownload(function () use ($customers, $birthdays) {
            $columns = [
                'ID' => fn ($c) => $c->id,
                'First name' => fn ($c) => $c->first_name,
                'Last name' => fn ($c) => $c->last_name,
                'Preferred name' => fn ($c) => $c->preferred_name,
                'Email' => fn ($c) => $c->email,
                'Phone' => fn ($c) => $c->phone,
                'WhatsApp' => fn ($c) => $c->whatsapp,
                'Date of birth' => fn ($c) => $c->date_of_birth?->format('Y-m-d'),
                'City' => fn ($c) => $c->city,
                'Language' => fn ($c) => $c->language,
                'Status' => fn ($c) => $c->status?->name,
                'Tags' => fn ($c) => $c->tags->pluck('name')->implode(', '),
                'Marketing eligible' => fn ($c) => $c->isMarketingEligible() ? 'yes' : 'no',
                'Source' => fn ($c) => Customer::SOURCES[$c->source] ?? $c->source,
                'Assigned to' => fn ($c) => $c->assignedUser?->name,
                'Trips' => fn ($c) => $c->trips_count,
                'Total paid (CAD)' => fn ($c) => number_format((float) $c->total_paid, 2, '.', ''),
                'Outstanding (CAD)' => fn ($c) => number_format((float) $c->outstanding, 2, '.', ''),
                'Last trip' => fn ($c) => $c->last_trip_at ? substr($c->last_trip_at, 0, 10) : null,
                'Signed up' => fn ($c) => $c->created_at?->format('Y-m-d'),
                'Last activity' => fn ($c) => $c->last_activity_at?->format('Y-m-d H:i'),
            ];
            if (!$birthdays) {
                unset($columns['Date of birth']);
            }
            $out = fopen('php://output', 'w');
            fputcsv($out, array_keys($columns));
            foreach ($customers as $c) {
                fputcsv($out, array_map(fn ($value) => $value($c) ?? '', array_values($columns)));
            }
            fclose($out);
        }, 'dreamtour-customers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
