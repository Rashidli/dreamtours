<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Tour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Customer-submitted reviews. They stay hidden until an admin approves them. */
class ReviewController extends Controller
{
    public function store(Request $request, string $slug): RedirectResponse
    {
        $tour = Tour::where('status', 'published')->whereTranslation('slug', $slug)->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'travel_date' => ['nullable', 'date', 'before_or_equal:today'],
            'content' => ['required', 'string', 'min:20', 'max:3000'],
            'website' => ['prohibited'], // honeypot
        ]);

        Review::create([
            'tour_id' => $tour->id,
            'email' => $data['email'],
            'travel_date' => $data['travel_date'] ?? null,
            'rating' => $data['rating'],
            'status' => 'pending',
            'is_active' => false,
            'order' => 0,
            'en' => ['name' => $data['name'], 'position' => $data['city'] ?? null, 'content' => $data['content']],
        ]);

        return redirect()->to(route('front.tours.show', $slug).'#reviews')
            ->with('review_message', 'Thank you! Your review has been sent and will appear after it is approved.');
    }
}
