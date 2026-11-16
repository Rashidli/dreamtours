<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\SharesFrontLayout;
use App\Models\Tour;
use App\Models\TourDeparture;
use App\Support\Waitlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** "Join the waitlist" for a full date on the website (CRM §8). */
class WaitlistPageController extends Controller
{
    use SharesFrontLayout;

    public function create(Request $request, string $slug): View|RedirectResponse
    {
        [$tour, $departure] = $this->resolve($request, $slug);
        if (!$departure) {
            return redirect()->route('front.tours.show', $slug);
        }

        return view('front.pages.waitlist', ['tour' => $tour, 'departure' => $departure, 'joined' => session('waitlist_joined')] + $this->layoutData());
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        [$tour, $departure] = $this->resolve($request, $slug);
        if (!$departure) {
            return redirect()->route('front.tours.show', $slug)->with('message', 'Seats are available again on this date — you can book it now.');
        }
        if (filled($request->input('website'))) {
            return back(); // the honeypot field is invisible to people
        }
        $d = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'guests' => ['required', 'integer', 'min:1', 'max:20'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        Waitlist::add($departure, $d, null);

        return redirect()->route('front.waitlist.create', ['slug' => $slug, 'departure' => $departure->id])->with('waitlist_joined', $d['first_name']);
    }

    /** @return array{0: Tour, 1: ?TourDeparture} the departure only while it takes a waitlist */
    private function resolve(Request $request, string $slug): array
    {
        $tour = Tour::where('status', 'published')->whereTranslation('slug', $slug)->with('translations')->firstOrFail();
        $departure = TourDeparture::visible()->where('tour_id', $tour->id)->find($request->integer('departure'));

        return [$tour, $departure && Waitlist::open($departure->setRelation('tour', $tour)) ? $departure : null];
    }
}
