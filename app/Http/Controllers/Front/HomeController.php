<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Contact;
use App\Models\ContactItem;
use App\Models\Faq;
use App\Models\Portfolio;
use App\Models\Review;
use App\Models\Service;
use App\Models\Single;
use App\Models\Slider;
use App\Models\Social;
use App\Models\WhyUs;
use App\Models\About;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function welcome()
    {
        $sliders = Slider::active()->ordered()->get();
        $services = Service::active()->ordered()->get();
        $portfolios = Portfolio::active()->featured()->ordered()->take(6)->get();
        $blogs = Blog::active()->featured()->latest()->take(3)->get();
        $reviews = Review::active()->ordered()->get();
        $faqs = Faq::active()->ordered()->get();
        $whyUs = WhyUs::active()->ordered()->get();
        $about = About::getByKey('home');
        $seo = Single::where('type', 'home')->first();

        return view('front.welcome', compact(
            'sliders', 'services', 'portfolios', 'blogs', 'reviews', 'faqs', 'whyUs', 'about', 'seo'
        ));
    }

    public function dynamicPage($slug)
    {
        // Check for service
        $service = Service::whereTranslation('slug', $slug)->active()->first();
        if ($service) {
            return view('front.service_single', compact('service'));
        }

        // Check for blog
        $blog = Blog::whereTranslation('slug', $slug)->active()->first();
        if ($blog) {
            $blog->incrementView();
            $relatedBlogs = Blog::active()->where('id', '!=', $blog->id)->latest()->take(3)->get();
            return view('front.blog_single', compact('blog', 'relatedBlogs'));
        }

        // Check for portfolio
        $portfolio = Portfolio::whereTranslation('slug', $slug)->active()->first();
        if ($portfolio) {
            return view('front.portfolio_single', compact('portfolio'));
        }

        // Check for single page (SEO pages like about, contact, services, etc.)
        $single = Single::whereTranslation('slug', $slug)->first();
        if ($single) {
            switch ($single->type) {
                case 'about':
                    $about = About::getByKey('about');
                    $whyUs = WhyUs::active()->ordered()->get();
                    return view('front.about', compact('single', 'about', 'whyUs'));

                case 'services':
                    $services = Service::active()->ordered()->get();
                    return view('front.services', compact('single', 'services'));

                case 'portfolio':
                    $portfolios = Portfolio::active()->ordered()->paginate(12);
                    return view('front.portfolios', compact('single', 'portfolios'));

                case 'blogs':
                    $blogs = Blog::active()->latest()->paginate(12);
                    return view('front.blogs', compact('single', 'blogs'));

                case 'contact':
                    $contactItems = ContactItem::active()->ordered()->get();
                    $services = Service::active()->ordered()->get();
                    return view('front.contact', compact('single', 'contactItems', 'services'));

                default:
                    abort(404);
            }
        }

        abort(404);
    }

    public function contact_post(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
        ], [
            'name.required' => 'Ad, Soyad sahəsi mütləqdir.',
            'name.max' => 'Ad, Soyad 255 simvoldan çox ola bilməz.',
            'phone.required' => 'Telefon sahəsi mütləqdir.',
            'phone.max' => 'Telefon 50 simvoldan çox ola bilməz.',
        ]);

        Contact::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'message' => $request->message,
            'service_id' => $request->service_id,
        ]);

        return redirect()->route('success');
    }

    public function success()
    {
        return view('front.success');
    }
}
