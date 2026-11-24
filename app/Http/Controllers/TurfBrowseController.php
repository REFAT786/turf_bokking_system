<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Turf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TurfBrowseController extends Controller
{
    public function home()
    {
        $turfs = Turf::approved()->withAvg('reviews', 'rating')->latest()->take(6)->get();
        $areas = Turf::approved()->select('area', 'city')->distinct()->orderBy('area')->get();
        $stats = [
            'turfs' => Turf::approved()->count(),
            'areas' => $areas->count(),
            'players' => \App\Models\User::where('role', 'customer')->count(),
            'games' => \App\Models\Booking::whereIn('status', ['confirmed', 'completed'])->count(),
        ];

        return view('home', compact('turfs', 'areas', 'stats'));
    }

    public function index(Request $request)
    {
        $q = Turf::approved()->withAvg('reviews', 'rating')->withCount('reviews');

        $lat = $request->float('lat') ?: null;
        $lng = $request->float('lng') ?: null;
        if ($lat && $lng) {
            $q->nearest($lat, $lng);
            if ($radius = $request->integer('radius')) {
                $q->having('distance_km', '<=', $radius);
            }
        }

        if ($search = trim((string) $request->input('q'))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")
                ->orWhere('area', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%"));
        }
        if ($city = $request->input('city')) {
            $q->where('city', $city);
        }
        if ($maxPrice = $request->integer('max_price')) {
            $q->where('price_per_hour', '<=', $maxPrice);
        }

        match ($request->input('sort')) {
            'price' => $q->orderBy('price_per_hour'),
            'rating' => $q->orderByDesc('reviews_avg_rating'),
            default => ($lat && $lng) ? null : $q->latest(),
        };

        $turfs = $q->paginate(12)->withQueryString();
        $cities = Turf::approved()->distinct()->orderBy('city')->pluck('city');

        // Optional: show free slot count for a chosen date
        $date = $this->dateInput($request, 'date')?->startOfDay();

        return view('turfs.index', compact('turfs', 'cities', 'lat', 'lng', 'date'));
    }

    public function show(Request $request, Turf $turf)
    {
        abort_unless($turf->isLive() || $request->user()?->canManageTurf($turf) || $request->user()?->isSuperAdmin(), 404);

        $maxDays = (int) Setting::get('max_advance_booking_days');
        $date = $this->dateInput($request, 'date', today())->startOfDay();
        if ($date->lessThan(today()) || $date->greaterThan(today()->addDays($maxDays))) {
            $date = today();
        }

        $slots = $turf->slotsFor($date);
        $days = collect(range(0, $maxDays))->map(fn ($i) => today()->addDays($i));
        $reviews = $turf->reviews()->with('user:id,name')->latest()->take(10)->get();
        $rating = $turf->reviews()->avg('rating');

        return view('turfs.show', compact('turf', 'date', 'slots', 'days', 'reviews', 'rating'));
    }
}
