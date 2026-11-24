<?php

use Illuminate\Support\Facades\Schedule;

// Auto-cancel unpaid bookings once the payment deadline (set by super admin) passes.
Schedule::command('bookings:expire')->everyMinute()->withoutOverlapping();
