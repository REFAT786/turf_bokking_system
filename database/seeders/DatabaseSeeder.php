<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Turf;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create(['name' => 'Super Admin', 'email' => 'admin@turfbook.test', 'phone' => '01700000000', 'password' => 'password', 'role' => 'super_admin']);

        $owner = User::create(['name' => 'Rahim Uddin', 'business_name' => 'Kickoff Sports Ltd', 'email' => 'owner@turfbook.test', 'phone' => '01711111111', 'password' => 'password', 'role' => 'owner', 'verification_status' => 'approved', 'verified_at' => now()]);
        $staffUser = User::create(['name' => 'Karim (Manager)', 'email' => 'staff@turfbook.test', 'phone' => '01722222222', 'password' => 'password', 'role' => 'staff', 'owner_id' => $owner->id]);
        $player = User::create(['name' => 'Tanvir Ahmed', 'email' => 'player@turfbook.test', 'phone' => '01733333333', 'password' => 'password', 'role' => 'customer']);

        $turfs = [
            ['Kickoff Arena', 'Mirpur 10', 23.8069, 90.3687, 1500, 2000, '18:00', '06:00', '02:00'],
            ['Green Field Rooftop', 'Dhanmondi 27', 23.7561, 90.3735, 1800, 2200, '17:00', '07:00', '00:00'],
            ['Uttara Football Hub', 'Uttara Sector 7', 23.8728, 90.3984, 1400, 1800, '18:00', '06:00', '01:00'],
            ['Bashundhara Turf Zone', 'Bashundhara R/A', 23.8193, 90.4526, 2000, 2500, '18:00', '06:00', '03:00'],
            ['Gulshan Goal Garden', 'Gulshan 1', 23.7806, 90.4163, 2500, 3000, '17:30', '07:00', '00:00'],
            ['Mohammadpur Strikers', 'Mohammadpur', 23.7662, 90.3589, 1200, 1500, '18:00', '06:00', '00:00'],
        ];

        foreach ($turfs as $i => [$name, $area, $lat, $lng, $day, $night, $nightAt, $open, $close]) {
            Turf::create([
                'owner_id' => $owner->id, 'name' => $name, 'slug' => \Str::slug($name), 'area' => $area, 'city' => 'Dhaka',
                'address' => 'Road '.($i + 3).', '.$area, 'latitude' => $lat, 'longitude' => $lng, 'phone' => '01711111111',
                'size' => ['5-a-side', '7-a-side'][$i % 2], 'description' => "Premium artificial turf with FIFA-quality grass and LED floodlights.\nPerfect for evening matches with friends and office teams.",
                'facilities' => ['parking', 'floodlights', 'washroom', 'drinking_water', 'changing_room'],
                'price_per_hour' => $day, 'night_price_per_hour' => $night, 'night_starts_at' => $nightAt,
                'opens_at' => $open, 'closes_at' => $close, 'slot_minutes' => 60, 'advance_percent' => 30,
                'bkash_number' => '01711111111', 'nagad_number' => '01811111111',
                'payment_note' => 'Use Send Money and write your booking code as reference.',
                'status' => $i === 5 ? 'pending' : 'approved',
            ]);
        }

        // Demo: the manager runs two of the owner's turfs (staff can be responsible for several).
        $staffUser->assignedTurfs()->sync($owner->turfs()->orderBy('id')->take(2)->pluck('id'));

        // Demo bookings
        $svc = app(BookingService::class);
        $t = Turf::first();
        $tomorrow = today()->addDay();
        $b = $svc->create($t, [$tomorrow->copy()->setTime(19, 0)->toDateTimeString(), $tomorrow->copy()->setTime(20, 0)->toDateTimeString()], ['name' => $player->name, 'phone' => $player->phone], $player);
        $b->update(['status' => 'payment_submitted', 'payment_method' => 'bkash', 'payer_number' => $player->phone, 'trx_id' => 'BHK7X2LMQ9', 'paid_amount' => $b->advance_amount, 'payment_submitted_at' => now()]);
        $svc->create($t, [$tomorrow->copy()->setTime(21, 0)->toDateTimeString()], ['name' => $player->name, 'phone' => $player->phone], $player);
        $svc->create($t, [$tomorrow->copy()->setTime(17, 0)->toDateTimeString()], ['name' => 'Walk-in Sabbir', 'phone' => '01944444444', 'paid_amount' => 500], null, 'walk_in', $owner);

        // Past completed booking for reports/reviews
        Booking::create([
            'turf_id' => $t->id, 'user_id' => $player->id, 'customer_name' => $player->name, 'customer_phone' => $player->phone,
            'starts_at' => today()->subDays(2)->setTime(20, 0), 'ends_at' => today()->subDays(2)->setTime(21, 0),
            'total_amount' => 2000, 'advance_amount' => 600, 'paid_amount' => 2000, 'status' => 'completed',
            'payment_method' => 'bkash', 'trx_id' => 'BHK1AAA111', 'confirmed_at' => now()->subDays(3),
        ]);
    }
}
