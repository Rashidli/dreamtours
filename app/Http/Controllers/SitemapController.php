<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Portfolio;
use App\Models\Service;
use App\Models\Single;
use Illuminate\Http\Response;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class SitemapController extends Controller
{
    public function index()
    {
        $locales = LaravelLocalization::getSupportedLanguagesKeys();

        // Get all active content
        $services = Service::active()->ordered()->get();
        $portfolios = Portfolio::active()->ordered()->get();
        $blogs = Blog::active()->latest()->get();

        // Get static pages
        $homePage = Single::where('key', 'home')->first();
        $aboutPage = Single::where('key', 'about')->first();
        $servicePage = Single::where('key', 'services')->first();
        $portfolioPage = Single::where('key', 'portfolio')->first();
        $blogPage = Single::where('key', 'blog')->first();
        $contactPage = Single::where('key', 'contact')->first();

        $content = view('sitemap', compact(
            'locales',
            'services',
            'portfolios',
            'blogs',
            'homePage',
            'aboutPage',
            'servicePage',
            'portfolioPage',
            'blogPage',
            'contactPage'
        ))->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }
}
