<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\CustomerMerge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Possible duplicates and the side-by-side merge review (§3, §4). */
class CustomerMergeController extends Controller
{
    public function __construct() { $this->middleware('permission:merge-customers'); }

    public function index(): View
    {
        return view('admin.customers.merge.index', ['pairs' => CustomerMerge::suggestions()]);
    }

    public function compare(Request $r): View|RedirectResponse
    {
        $d = $r->validate(['keep' => ['required', 'integer'], 'other' => ['required', 'integer', 'different:keep']], ['other.different' => 'Choose two different customers.']);
        $keep = Customer::with(['status', 'tags', 'assignedUser'])->withCount(['bookings', 'visaEnquiries'])->find($d['keep']);
        $other = Customer::with(['status', 'tags', 'assignedUser'])->withCount(['bookings', 'visaEnquiries'])->find($d['other']);
        if (!$keep || !$other) {
            return redirect()->route('customer-merge.index')->withErrors(['other' => 'Customer #'.(!$keep ? $d['keep'] : $d['other']).' was not found (or is archived).']);
        }

        return view('admin.customers.merge.compare', ['keep' => $keep, 'other' => $other, 'fields' => CustomerMerge::FIELDS]);
    }

    public function merge(Request $r): RedirectResponse
    {
        $d = $r->validate([
            // Archived and already-merged customers can't take part in a merge.
            'keep' => ['required', 'integer', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'other' => ['required', 'integer', 'different:keep', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'choice' => ['array'],
            'choice.*' => [Rule::in(['keep', 'other'])],
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => 'Tick the box to confirm the merge.', 'other.exists' => 'That customer is archived or already merged.', 'keep.exists' => 'That customer is archived or already merged.']);
        $keep = Customer::findOrFail($d['keep']);
        $moved = CustomerMerge::merge($keep, Customer::findOrFail($d['other']), $d['choice'] ?? []);

        return redirect()->route('customers.show', $keep)->with('message', "Merged {$moved['merged customer']} into this customer. Bookings moved: {$moved['bookings moved']}.");
    }

    public function dismiss(Request $r): RedirectResponse
    {
        $d = $r->validate(['a' => ['required', 'integer', 'exists:customers,id'], 'b' => ['required', 'integer', 'different:a', 'exists:customers,id']]);
        CustomerMerge::dismiss(Customer::findOrFail($d['a']), Customer::findOrFail($d['b']), $r->user()->id);

        return back()->with('message', 'Marked as not duplicates. This pair will not be suggested again.');
    }
}
