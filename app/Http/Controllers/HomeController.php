<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Job;
use App\Models\Blog;
use App\Models\Lead;
use App\Models\BlogCategory;

class HomeController extends Controller
{


    public function index()
{
    $featuredBusinesses = Business::where('status', 'active')
        ->orderByRaw("FIELD(plan, 'featured', 'premium', 'free')")
        ->latest()
        ->limit(6)
        ->get();

    $premiumBusinesses = Business::where('status', 'active')
        ->whereIn('plan', ['premium', 'featured'])
        ->orderByRaw("FIELD(plan, 'featured', 'premium')")
        ->latest()
        ->limit(6)
        ->get();

    $latestJobs = Job::where('status', 'active')
        ->latest()
        ->limit(4)
        ->get();

    // Tourism category_id 9 ignore
    $latestBlogs = Blog::with('category')
        ->where('status', 'active')
        ->where('category_id', '!=', 9)
        ->latest()
        ->limit(4)
        ->get();

    $newLeads = Lead::where('status', 'new')
        ->latest()
        ->limit(3)
        ->get();

    $cities = collect(service_cities());
    $categories = collect(service_categories());

    $heroPromos = config('site.hero_promos', []);
    $popularSearches = config('site.popular_searches', []);

    // Tourism blogs
    $tourismCategory = BlogCategory::where('slug', 'tourism')->first();
    $tourismCategoryId = $tourismCategory ? $tourismCategory->id : null;

    $tourismBlogs = collect();

    if ($tourismCategoryId) {
        $tourismBlogs = Blog::where('status', 'active')
            ->where('category_id', $tourismCategoryId)
            ->latest()
            ->limit(4)
            ->get();
    }

    return view('home.index', compact(
        'featuredBusinesses',
        'premiumBusinesses',
        'latestJobs',
        'latestBlogs',
        'newLeads',
        'cities',
        'categories',
        'heroPromos',
        'popularSearches',
        'tourismBlogs',
        'tourismCategoryId'
    ));
}
}
