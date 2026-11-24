<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Setting;
use App\Models\Turf;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * Create a booking for consecutive slots starting at the given ISO datetimes.
     *
     * @param  string[]  $slotStarts  e.g. ["2026-11-20 18:00:00", "2026-11-20 19:00:00"]
     */
    public function create(Turf $turf, array $slotStarts, array $customer, ?User $user, string $source = 'online', ?User $handler = null): Booking
    {
        $starts = collect($slotStarts)
            ->map(fn ($s) => Carbon::parse($s))
            ->sort()
            ->values();

        if ($starts->isEmpty()) {
            throw ValidationException::withMessages(['slots' => 'Please select at least one time slot.']);
        }
        if ($starts->count() > 6) {
            throw ValidationException::withMessages(['slots' => 'You can book at most 6 slots at once.']);
        }

        // Slots must be consecutive.
        foreach ($starts as $i => $s) {
            if ($i > 0 && ! $starts[$i - 1]->copy()->addMinutes($turf->slot_minutes)->equalTo($s)) {
                throw ValidationException::withMessages(['slots' => 'Please select consecutive time slots.']);
            }
        }

        $first = $starts->first();
        $end = $starts->last()->copy()->addMinutes($turf->slot_minutes);

        if ($first->isPast()) {
            throw ValidationException::withMessages(['slots' => 'That time has already passed.']);
        }
        $maxDays = (int) Setting::get('max_advance_booking_days');
        if ($source === 'online' && $first->greaterThan(now()->addDays($maxDays)->endOfDay())) {
            throw ValidationException::withMessages(['slots' => "Bookings open only {$maxDays} days in advance."]);
        }

        // Every requested start must be a real slot of this turf on that business day.
        $validStarts = collect($turf->slotsFor($this->businessDay($turf, $first)))
            ->keyBy(fn ($slot) => $slot['start']->toDateTimeString());
        $total = 0;
        foreach ($starts as $s) {
            $slot = $validStarts[$s->toDateTimeString()] ?? null;
            if (! $slot) {
                throw ValidationException::withMessages(['slots' => 'Invalid time slot selected.']);
            }
            $total += $slot['price'];
        }

        return DB::transaction(function () use ($turf, $first, $end, $total, $customer, $user, $source, $handler) {
            // Lock the turf row so two people can't grab the same slot simultaneously.
            Turf::whereKey($turf->id)->lockForUpdate()->first();

            $clash = Booking::where('turf_id', $turf->id)
                ->blocking()
                ->where('starts_at', '<', $end)
                ->where('ends_at', '>', $first)
                ->exists();
            if ($clash) {
                throw ValidationException::withMessages(['slots' => 'Sorry, someone just booked one of these slots. Please choose another time.']);
            }

            $advance = (int) ceil($total * $turf->advance_percent / 100);
            $data = [
                'turf_id' => $turf->id,
                'user_id' => $user?->id,
                'customer_name' => $customer['name'],
                'customer_phone' => $customer['phone'],
                'starts_at' => $first,
                'ends_at' => $end,
                'total_amount' => $total,
                'advance_amount' => $advance,
                'source' => $source,
                'notes' => $customer['notes'] ?? null,
            ];

            if ($source === 'walk_in') {
                $paid = min($total, (int) ($customer['paid_amount'] ?? 0));
                $data += [
                    'status' => 'confirmed',
                    'paid_amount' => $paid,
                    'payment_method' => $paid > 0 ? 'cash' : null,
                    'confirmed_at' => now(),
                    'handled_by' => $handler?->id,
                ];
            } else {
                $deadline = now()->addHours((int) Setting::get('payment_deadline_hours'));
                // Never let an unpaid hold run past the start of the game.
                if ($deadline->greaterThan($first)) {
                    $deadline = $first->copy();
                }
                $data += ['status' => 'pending_payment', 'payment_deadline_at' => $deadline];
            }

            return Booking::create($data);
        });
    }

    /** The calendar date whose opening hours contain this slot (handles after-midnight slots). */
    public function businessDay(Turf $turf, Carbon $slotStart): Carbon
    {
        $day = $slotStart->copy()->startOfDay();
        $crossesMidnight = $turf->closes_at <= $turf->opens_at;
        if ($crossesMidnight && $slotStart->format('H:i:s') < $turf->opens_at) {
            $day->subDay();
        }

        return $day;
    }
}
