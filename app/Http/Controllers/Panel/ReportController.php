<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $turfs = $request->user()->managedTurfs()->get();
        $month = $this->dateInput($request, 'month', now(), 'Y-m')->startOfMonth();
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();

        $q = fn () => Booking::whereIn('turf_id', $turfs->pluck('id'))->whereBetween('starts_at', [$from, $to]);

        $byTurf = $turfs->map(function ($turf) use ($q) {
            $rows = $q()->where('turf_id', $turf->id)->get();
            $played = $rows->whereIn('status', ['confirmed', 'completed']);

            return [
                'turf' => $turf,
                'bookings' => $played->count(),
                'hours' => round($played->sum(fn ($b) => $b->hours()), 1),
                'revenue' => $played->sum('total_amount'),
                'collected' => $rows->whereIn('status', ['confirmed', 'completed', 'no_show'])->sum('paid_amount'),
                'cancelled' => $rows->where('status', 'cancelled')->count(),
                'no_show' => $rows->where('status', 'no_show')->count(),
                'online' => $played->where('source', 'online')->count(),
                'walk_in' => $played->where('source', 'walk_in')->count(),
            ];
        });

        $daily = $q()->whereIn('status', ['confirmed', 'completed'])
            ->selectRaw('DATE(starts_at) as d, SUM(total_amount) as revenue, COUNT(*) as n')
            ->groupBy('d')->orderBy('d')->get();

        $byMethod = $q()->whereIn('status', ['confirmed', 'completed', 'no_show'])->whereNotNull('payment_method')
            ->selectRaw('payment_method, SUM(paid_amount) as total, COUNT(*) as n')
            ->groupBy('payment_method')->get();

        return view('panel.reports', compact('byTurf', 'daily', 'byMethod', 'month'));
    }
}
