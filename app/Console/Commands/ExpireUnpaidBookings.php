<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;

class ExpireUnpaidBookings extends Command
{
    protected $signature = 'bookings:expire';

    protected $description = 'Auto-cancel bookings whose advance payment deadline has passed';

    public function handle(): int
    {
        $n = Booking::expireOverdue();
        $this->info("Cancelled {$n} unpaid booking(s).");

        return self::SUCCESS;
    }
}
