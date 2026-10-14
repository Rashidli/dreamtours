<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VisaEnquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VisaEnquiryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:list-visa-enquiries')->only(['index', 'show']);
        $this->middleware('permission:edit-visa-enquiries')->only('update');
        $this->middleware('permission:delete-visa-enquiries')->only('destroy');
    }

    public function index(Request $request): View
    {
        $enquiries = VisaEnquiry::query()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('reference', 'like', '%'.$request->q.'%')
                ->orWhere('full_name', 'like', '%'.$request->q.'%')->orWhere('email', 'like', '%'.$request->q.'%')
                ->orWhere('phone', 'like', '%'.$request->q.'%')->orWhere('country', 'like', '%'.$request->q.'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.visa_enquiries.index', ['enquiries' => $enquiries, 'newCount' => VisaEnquiry::where('status', 'new')->count()]);
    }

    public function show(VisaEnquiry $visa_enquiry): View
    {
        return view('admin.visa_enquiries.show', ['enquiry' => $visa_enquiry]);
    }

    public function update(Request $request, VisaEnquiry $visa_enquiry): RedirectResponse
    {
        $visa_enquiry->update($request->validate([
            'status' => ['required', Rule::in(array_keys(VisaEnquiry::STATUSES))],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]));

        return back()->with('message', 'Enquiry updated.');
    }

    public function destroy(VisaEnquiry $visa_enquiry): RedirectResponse
    {
        $visa_enquiry->delete();

        return redirect()->route('visa-enquiries.index')->with('message', 'Enquiry deleted.');
    }
}
