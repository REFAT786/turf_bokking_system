<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $turfIds = $user->managedTurfs()->pluck('id');
        $base = fn () => Booking::whereIn('turf_id', $turfIds);

        $stats = [
            'today' => $base()->whereIn('status', ['confirmed', 'completed', 'payment_submitted'])
                ->whereBetween('starts_at', [today(), today()->endOfDay()])->count(),
            'to_verify' => $base()->where('status', 'payment_submitted')->count(),
            'awaiting' => $base()->where('status', 'pending_payment')->blocking()->count(),
            'month_revenue' => $base()->whereIn('status', ['confirmed', 'completed'])
                ->whereBetween('starts_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total_amount'),
            'month_collected' => $base()->whereIn('status', ['confirmed', 'completed', 'no_show'])
                ->whereBetween('starts_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('paid_amount'),
        ];

        $toVerify = $base()->with('turf')->where('status', 'payment_submitted')->orderBy('starts_at')->take(10)->get();
        $todayList = $base()->with('turf')->whereIn('status', ['confirmed', 'completed', 'payment_submitted', 'pending_payment'])
            ->blocking()
            ->whereBetween('starts_at', [today(), today()->endOfDay()->addHours(6)])
            ->orderBy('starts_at')->get();
        $turfs = $user->managedTurfs()->get();

        return view('panel.dashboard', compact('stats', 'toVerify', 'todayList', 'turfs'));
    }
}
