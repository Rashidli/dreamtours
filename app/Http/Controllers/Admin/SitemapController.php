<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Models\Single;
use Illuminate\Http\Request;

class SitemapController extends Controller
{
    public function sitemap()
    {
        $singles = Single::with('translations')->get();
        $services = Service::active()->with('translations')->get();
        $blogs = Blog::active()->with('translations')->get();
        $projects = Project::active()->with('translations')->get();
        $products = Product::active()->with('translations')->get();

        return response()->view('front.sitemap', compact('singles', 'blogs', 'services', 'projects', 'products'))->header('Content-type','text/xml');
    }
}
