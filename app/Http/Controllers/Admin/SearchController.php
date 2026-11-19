<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Tour;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/** Top bar search: one box for tours, customers, bookings and leads (each only with its list permission). */
class SearchController extends Controller
{
    private const LIMIT = 8;

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $user = $request->user();
        $groups = [];

        if (mb_strlen($q) >= 2) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
            $digits = preg_replace('/\D+/', '', $q);

            if ($user->can('list-tours')) {
                $groups['Tours'] = Tour::query()->with('translations')->whereTranslationLike('name', $like)->orderBy('sort_order')->limit(self::LIMIT)->get()
                    ->map(fn (Tour $t) => ['title' => $t->name, 'meta' => ucfirst((string) $t->status), 'url' => route('tours.edit', $t), 'icon' => 'ri-suitcase-line']);
            }
            if ($user->can('list-customers')) {
                $groups['Customers'] = Customer::query()
                    ->where(fn ($w) => $w->whereRaw("concat_ws(' ', first_name, last_name) like ?", [$like])->orWhere('email', 'like', $like)->orWhere('preferred_name', 'like', $like)
                        ->when(strlen($digits) >= 4, fn ($x) => $x->orWhere('phone_digits', 'like', '%'.$digits.'%')))
                    ->latest('last_activity_at')->limit(self::LIMIT)->get()
                    ->map(fn (Customer $c) => ['title' => trim($c->first_name.' '.$c->last_name), 'meta' => collect([$c->email, $c->phone])->filter()->implode(' · '), 'url' => route('customers.show', $c), 'icon' => 'ri-contacts-book-line']);
            }
            if ($user->can('list-bookings')) {
                $groups['Bookings'] = Booking::query()
                    ->where(fn ($w) => $w->where('reference', 'like', $like)->orWhereRaw("concat_ws(' ', first_name, last_name) like ?", [$like])->orWhere('email', 'like', $like)
                        ->when(strlen($digits) >= 4, fn ($x) => $x->orWhereRaw("regexp_replace(phone, '[^0-9]', '') like ?", ['%'.$digits.'%'])))
                    ->latest()->limit(self::LIMIT)->get()
                    ->map(fn (Booking $b) => ['title' => $b->reference.' · '.trim($b->first_name.' '.$b->last_name), 'meta' => collect([ucfirst(str_replace('_', ' ', (string) $b->status)), $b->created_at?->format('M j, Y')])->filter()->implode(' · '), 'url' => route('bookings.show', $b), 'icon' => 'ri-calendar-check-line']);
            }
            if ($user->can('list-leads')) {
                $groups['Leads'] = Lead::query()
                    ->where(fn ($w) => $w->where('reference', 'like', $like)->orWhereRaw("concat_ws(' ', first_name, last_name) like ?", [$like])->orWhere('email', 'like', $like)
                        ->when(strlen($digits) >= 4, fn ($x) => $x->orWhereRaw("regexp_replace(phone, '[^0-9]', '') like ?", ['%'.$digits.'%'])))
                    ->latest()->limit(self::LIMIT)->get()
                    ->map(fn (Lead $l) => ['title' => trim($l->first_name.' '.$l->last_name) ?: $l->reference, 'meta' => collect([$l->reference, $l->email])->filter()->implode(' · '), 'url' => route('leads.show', $l), 'icon' => 'ri-user-star-line']);
            }
        }

        return view('admin.search.index', ['q' => $q, 'groups' => collect($groups)->filter(fn ($items) => $items->isNotEmpty())]);
    }
}
