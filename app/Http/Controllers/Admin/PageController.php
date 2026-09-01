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

    public function register()
    {
        return view('register');
    }

    public function home()
    {
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

        return view('home', compact('stats'));
    }
}
