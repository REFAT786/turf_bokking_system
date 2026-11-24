<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Fallback for hosts without cron: sweep overdue unpaid bookings at most once a minute
 * while people are using the site. (Slot availability already ignores overdue holds.)
 */
class ExpireOverdueBookings
{
    public function handle(Request $request, Closure $next)
    {
        if (Cache::add('bookings:expire-sweep', 1, 60)) {
            Booking::expireOverdue();
        }

        return $next($request);
    }
}
