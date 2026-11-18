<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Support\TourCopier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Tours → Duplicate: makes a Draft copy and opens it for editing. */
class TourDuplicateController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:create-tours');
    }

    public function store(Request $request, Tour $tour): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'itinerary' => ['nullable', 'boolean'],
            'content' => ['nullable', 'boolean'],
            'dates' => ['nullable', 'boolean'],
        ]);

        $copy = TourCopier::copy($tour, [
            'name' => $data['name'],
            'itinerary' => $request->boolean('itinerary'),
            'content' => $request->boolean('content'),
            'dates' => $request->boolean('dates'),
        ]);

        return redirect()->route('tours.edit', $copy)
            ->with('message', 'Copy created as a Draft. Change what is different, then set the status to Published.');
    }
}
