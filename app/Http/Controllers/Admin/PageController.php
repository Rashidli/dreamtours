<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Contact;
use App\Models\GalleryAlbum;
use App\Models\Page;
use App\Models\Tour;
use App\Models\User;
use App\Models\VisaService;

class PageController extends Controller
{
    public function login()
    {
        return view('login');
    }

    public function home()
    {
        // Trip staff (a tour leader, a driver) land on their own trips, not on company-wide figures.
        $user = auth()->user();
        if (!$user->can('list-bookings') && !$user->can('list-tours') && ($user->can('list-assigned-departures') || $user->tripAssignments()->exists())) {
            return redirect()->route('my-trips.index');
        }
        $stats = [
            'tours' => Tour::count(),
            'published_tours' => Tour::where('status', 'published')->count(),
            'bookings' => Booking::count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(),
            'pages' => Page::count(),
            'gallery_albums' => GalleryAlbum::count(),
            'visa_services' => VisaService::count(),
            'contacts' => Contact::count(),
            'unread_contacts' => Contact::where('is_read', false)->count(),
            'users' => User::count(),
        ];

        return view('home', ['stats' => $stats, 'dash' => \App\Support\Dashboard::for($user)]);
    }
}
