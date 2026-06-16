<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;


class BusinessController extends Controller
{
   public function index(Request $request)
{
    $query = Business::query()->where('status', 'active');

    if ($request->filled('city')) {
        $query->where('city', 'like', '%' . $request->city . '%');
    }

    if ($request->filled('category')) {
        $query->where('category', 'like', '%' . $request->category . '%');
    }

    if ($request->filled('search')) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%')
              ->orWhere('category', 'like', '%' . $search . '%')
              ->orWhere('city', 'like', '%' . $search . '%')
              ->orWhere('address', 'like', '%' . $search . '%')
              ->orWhere('phone', 'like', '%' . $search . '%')
              ->orWhere('description', 'like', '%' . $search . '%');
        });
    }

    $businesses = $query
        ->orderByRaw("FIELD(plan, 'featured', 'premium', 'free')")
        ->orderBy('created_at', 'desc')
        ->paginate(20);

    $cities = service_cities();
    $categories = service_categories();

    return view('businesses.index', compact('businesses', 'cities', 'categories'));
}

   public function show($slug)
{
    $business = Business::where('slug', $slug)
        ->where('status', 'active')
        ->firstOrFail();

    $business->increment('views');

    $reviews = Review::where('business_id', $business->id)
        ->where('status', 'approved')
        ->latest()
        ->limit(10)
        ->get();

    $similarBusinesses = Business::where('status', 'active')
        ->where('id', '!=', $business->id)
        ->where(function ($query) use ($business) {
            $query->where('city', $business->city)
                  ->orWhere('category', $business->category);
        })
        ->orderByRaw("
            CASE
                WHEN city = ? AND category = ? THEN 1
                WHEN city = ? THEN 2
                WHEN category = ? THEN 3
                ELSE 4
            END
        ", [$business->city, $business->category, $business->city, $business->category])
        ->orderByRaw("FIELD(plan, 'featured', 'premium', 'free')")
        ->limit(6)
        ->get();

    $userReviewExists = false;
    if (auth()->check()) {
        $userReviewExists = Review::where('business_id', $business->id)
            ->where('user_id', auth()->id())
            ->exists();
    }

    $schema = [
    "@context" => "https://schema.org",
    "@type" => "LocalBusiness",
    "name" => $business->name,
    "image" => $business->image ? asset('storage/' . $business->image) : asset('images/default-og.jpg'),
    "description" => \Illuminate\Support\Str::limit(strip_tags($business->description ?? ''), 200),
    "telephone" => $business->phone,
    "address" => [
        "@type" => "PostalAddress",
        "streetAddress" => $business->address ?? '',
        "addressLocality" => $business->city ?? '',
        "addressRegion" => "Bihar",
        "addressCountry" => "IN",
    ],
    "url" => route('business.show', $business->slug),
];

if (!empty($business->review_count) && !empty($business->rating)) {
    $schema["aggregateRating"] = [
        "@type" => "AggregateRating",
        "ratingValue" => (string) $business->rating,
        "reviewCount" => (string) $business->review_count,
    ];
}

return view('businesses.show', compact(
    'business',
    'reviews',
    'similarBusinesses',
    'userReviewExists',
    'schema'
));
}
public function city($city)
{
    $cityName = ucwords(str_replace('-', ' ', $city));

    $featuredBusinesses = Business::where('status', 'active')
        ->where('city', 'like', $cityName)
        ->whereIn('plan', ['featured', 'premium'])
        ->orderByRaw("FIELD(plan, 'featured', 'premium')")
        ->limit(6)
        ->get();

    $businesses = Business::where('status', 'active')
        ->where('city', 'like', $cityName)
        ->orderByRaw("FIELD(plan, 'featured', 'premium', 'free')")
        ->orderBy('created_at', 'desc')
        ->paginate(20);

    $categories = Business::where('status', 'active')
        ->where('city', 'like', $cityName)
        ->select('category')
        ->distinct()
        ->orderBy('category')
        ->pluck('category');

    return view('businesses.city', compact('businesses', 'cityName', 'categories', 'featuredBusinesses'));
}

public function cityCategory($city, $category)
{
    $cityName = ucwords(str_replace('-', ' ', $city));
    $categoryName = ucwords(str_replace('-', ' ', $category));

    $featuredBusinesses = Business::where('status', 'active')
        ->where('city', 'like', $cityName)
        ->where('category', 'like', $categoryName)
        ->whereIn('plan', ['featured', 'premium'])
        ->orderByRaw("FIELD(plan, 'featured', 'premium')")
        ->limit(6)
        ->get();

    $businesses = Business::where('status', 'active')
        ->where('city', 'like', $cityName)
        ->where('category', 'like', $categoryName)
        ->orderByRaw("FIELD(plan, 'featured', 'premium', 'free')")
        ->orderBy('created_at', 'desc')
        ->paginate(20);

    return view('businesses.city-category', compact('businesses', 'cityName', 'categoryName', 'featuredBusinesses'));
}
   public function create()
{
    $cities = service_cities();
    $categories = service_categories();

    return view('businesses.create', compact('cities', 'categories'));
}

    public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:200',
        'category' => 'required|string|max:100',
        'city' => 'required|string|max:100',
        'address' => 'required|string',
        'phone' => 'required|string|max:20',
        'whatsapp' => 'nullable|string|max:20',
        'email' => 'nullable|email|max:150',
        'website' => 'nullable|string|max:300',
        'description' => 'nullable|string',
        'services' => 'nullable|string',
        'price_from' => 'nullable|numeric',
        'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'gallery.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $slug = Str::slug($validated['name']);
    $originalSlug = $slug;
    $counter = 1;

    while (Business::where('slug', $slug)->exists()) {
        $slug = $originalSlug . '-' . $counter;
        $counter++;
    }

    $imagePath = null;
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('businesses', 'public');
    }

    $galleryPaths = [];
    if ($request->hasFile('gallery')) {
        foreach ($request->file('gallery') as $galleryImage) {
            $galleryPaths[] = $galleryImage->store('businesses/gallery', 'public');
        }
    }

    // owner_id = 1 ho to null save karo
    $ownerId = auth()->id();
    $finalOwnerId = ($ownerId == 1) ? null : $ownerId;
    $isClaimed = ($ownerId == 1) ? null : 1;

    $business = Business::create([
        'owner_id' => $finalOwnerId,
        'is_claimed' => $isClaimed,
        'name' => $validated['name'],
        'slug' => $slug,
        'category' => $validated['category'],
        'city' => $validated['city'],
        'address' => $validated['address'],
        'phone' => $validated['phone'],
        'whatsapp' => $validated['whatsapp'] ?? null,
        'email' => $validated['email'] ?? null,
        'website' => $validated['website'] ?? null,
        'description' => $validated['description'] ?? null,
        'services' => $validated['services'] ?? null,
        'price_from' => $validated['price_from'] ?? 0,
        'image' => $imagePath,
        'gallery' => !empty($galleryPaths) ? json_encode($galleryPaths) : null,
        'featured' => 0,
        'plan' => 'free',
        'status' => 'pending',
        'rating' => 0,
        'review_count' => 0,
        'views' => 0,
        'verified' => 0,
        'payment_status' => 'pending',
    ]);

    // user role upgrade if needed
    $user = auth()->user();
    if ($user && $user->role === 'customer') {
        $user->update(['role' => 'business_owner']);
    }

    return redirect()->route('owner.businesses.index')
        ->with('success', 'Business submitted successfully. It is pending admin approval.');
}
    public function ajaxSearch(Request $request)
{
    $term = $request->get('q');

    if (!$term || strlen($term) < 2) {
        return response()->json([]);
    }

    $results = Business::where('status', 'active')
        ->where(function ($query) use ($term) {
            $query->where('name', 'like', '%' . $term . '%')
                  ->orWhere('category', 'like', '%' . $term . '%')
                  ->orWhere('city', 'like', '%' . $term . '%');
        })
        ->orderByRaw("FIELD(plan, 'featured', 'premium', 'free')")
        ->limit(8)
        ->get(['name', 'slug', 'city', 'category']);

    return response()->json($results);
}

public function appBasicSave(Request $request)
{
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'business_name' => 'required|string|max:255',
        'category' => 'required|string|max:255',
        'city' => 'required|string|max:100',
        'phone' => 'required|string|max:20',
        'whatsapp' => 'nullable|string|max:20',
        'address' => 'required|string|max:500',
    ]);

    $businessId = DB::table('businesses')->insertGetId([
       'owner_id' => $validated['user_id'],
        'name' => $validated['business_name'],
        'category' => $validated['category'],
        'city' => $validated['city'],
        'phone' => $validated['phone'],
        'whatsapp' => $validated['whatsapp'] ?? $validated['phone'],
        'address' => $validated['address'],
        'status' => 'pending',
        'profile_percentage' => 30,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('users')
        ->where('id', $validated['user_id'])
        ->update([
            'role' => 'business_owner',
            'updated_at' => now(),
        ]);

    $user = DB::table('users')->where('id', $validated['user_id'])->first();

    return response()->json([
        'success' => true,
        'message' => 'Business basic info saved.',
        'business' => [
            'id' => $businessId,
            'profile_percentage' => 30,
            'status' => 'pending',
        ],
        'user' => $user,
    ]);
}
public function appContactUpdate(Request $request)
{
    $validated = $request->validate([
        'business_id' => 'required|exists:businesses,id',
        'email' => 'nullable|email|max:150',
        'website' => 'nullable|string|max:255',
        'map_link' => 'nullable|string|max:1000',
    ]);

    DB::table('businesses')
        ->where('id', $validated['business_id'])
        ->update([
            'email' => $validated['email'] ?? null,
            'website' => $validated['website'] ?? null,
            'direction' => $validated['map_link'] ?? null,
            'profile_percentage' => 45,
            'updated_at' => now(),
        ]);

    $business = DB::table('businesses')
        ->where('id', $validated['business_id'])
        ->first();

    return response()->json([
        'success' => true,
        'message' => 'Contact info updated.',
        'business' => $business,
    ]);
}
public function appMyBusiness($userId)
{
    $business = DB::table('businesses')
        ->where('owner_id', $userId)
        ->latest('id')
        ->first();

    return response()->json([
        'success' => true,
        'business' => $business,
    ]);
}
public function appUpdateBusinessDescription(Request $request)
{
    $request->validate([
        'business_id' => 'required|exists:businesses,id',
        'description' => 'nullable|string',
        'services' => 'nullable|string',
    ]);

    $business = DB::table('businesses')
        ->where('id', $request->business_id)
        ->first();

    if (!$business) {
        return response()->json([
            'success' => false,
            'message' => 'Business not found.',
        ], 404);
    }

    DB::table('businesses')
        ->where('id', $request->business_id)
        ->update([
            'description' => $request->description,
            'services' => $request->tags,
            'updated_at' => now(),
        ]);

    $updatedBusiness = DB::table('businesses')
        ->where('id', $request->business_id)
        ->first();

    return response()->json([
        'success' => true,
        'message' => 'Description updated successfully.',
        'business' => $updatedBusiness,
    ]);
}
public function appMediaUpdate(Request $request)
{
    $validated = $request->validate([
        'business_id' => 'required|exists:businesses,id',
        'facebook_url' => 'nullable|string|max:255',
        'instagram_url' => 'nullable|string|max:255',
        'youtube_url' => 'nullable|string|max:255',
        'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4048',
        'gallery_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4048',
    ]);

    $business = DB::table('businesses')
        ->where('id', $validated['business_id'])
        ->first();

    if (!$business) {
        return response()->json([
            'success' => false,
            'message' => 'Business not found.',
        ], 404);
    }

    $coverImagePath = $business->image ?? $business->cover_image ?? null;

    if ($request->hasFile('cover_image')) {
        $coverImagePath = $request->file('cover_image')->store('businesses', 'public');
    }

    $galleryPaths = [];

    // IMPORTANT: website/admin uses gallery column
    if (!empty($business->gallery)) {
        $decodedGallery = json_decode($business->gallery, true);

        // If double encoded JSON ever exists, decode again safely
        if (is_string($decodedGallery)) {
            $decodedGallery = json_decode($decodedGallery, true);
        }

        $galleryPaths = is_array($decodedGallery) ? $decodedGallery : [];
    }

    if ($request->hasFile('gallery_images')) {
        foreach ($request->file('gallery_images') as $image) {
            $galleryPaths[] = $image->store('businesses/gallery', 'public');
        }
    }

    // Same as website format: ["businesses\/gallery\/file.jpg"]
    $galleryJson = json_encode(array_values($galleryPaths));

    DB::table('businesses')
        ->where('id', $validated['business_id'])
        ->update([
            'facebook' => $validated['facebook_url'] ?? null,
            'instagram' => $validated['instagram_url'] ?? null,
            'youtube' => $validated['youtube_url'] ?? null,
            'image' => $coverImagePath,
            'image' => $coverImagePath, // agar column hai to useful, nahi hai to hata dena
            'gallery' => $galleryJson,
            'profile_percentage' => 80,
            'updated_at' => now(),
        ]);

    $updatedBusiness = DB::table('businesses')
        ->where('id', $validated['business_id'])
        ->first();

    return response()->json([
        'success' => true,
        'message' => 'Social links and images updated successfully.',
        'business' => $updatedBusiness,
    ]);
}
public function appFinalUpdate(Request $request)
{
    $validated = $request->validate([
        'business_id' => 'required|exists:businesses,id',
        'offers' => 'nullable|string',
        'opening_hours' => 'nullable|string|max:255',
        'menu_card' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
    ]);

    $business = DB::table('businesses')
        ->where('id', $validated['business_id'])
        ->first();

    if (!$business) {
        return response()->json([
            'success' => false,
            'message' => 'Business not found.',
        ], 404);
    }

    $menuCardPath = $business->menu_card ?? null;

    if ($request->hasFile('menu_card')) {
        // format: businesses/menu/filename.jpg or pdf
        $menuCardPath = $request->file('menu_card')->store('businesses/menu', 'public');
    }

    DB::table('businesses')
        ->where('id', $validated['business_id'])
        ->update([
            'menu_cards' => $menuCardPath,
            'offers' => $validated['offers'] ?? null,
            'opening_hours' => $validated['opening_hours'] ?? null,
            'profile_percentage' => 100,
            'updated_at' => now(),
        ]);

    $updatedBusiness = DB::table('businesses')
        ->where('id', $validated['business_id'])
        ->first();

    return response()->json([
        'success' => true,
        'message' => 'Business profile completed successfully.',
        'business' => $updatedBusiness,
    ]);
}
private function getAppPlanConfig($plan)
{
    $plan = $plan ?: 'free';

    $plans = config('business_app.plans');

    return $plans[$plan] ?? $plans['free'];
}
}
