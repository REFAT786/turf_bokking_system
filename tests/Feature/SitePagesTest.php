<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Turf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Every page renders for every role with the demo data. */
class SitePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // explicit: RefreshDatabase only auto-seeds on the first migration of the run
    }

    private function user(string $email): User
    {
        return User::firstWhere('email', $email);
    }

    public function test_public_pages_render_for_guests(): void
    {
        $turf = Turf::approved()->first();
        foreach (['/', '/turfs', '/turfs?q=Mirpur&sort=price', '/how-it-works', '/login', '/register', '/register/owner', route('turfs.show', $turf)] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/')->assertSee('Popular turfs')->assertSee('images/art/hero.svg', false);
    }

    public function test_player_pages_render(): void
    {
        $player = $this->user('player@turfbook.test');
        $booking = Booking::where('user_id', $player->id)->first();

        $this->actingAs($player)->get('/my/bookings')->assertOk()->assertSee('My bookings');
        $this->actingAs($player)->get(route('my.bookings.show', $booking))->assertOk()->assertSee($booking->code);
        $this->actingAs($player)->get(route('turfs.show', $booking->turf))->assertOk()->assertSee('Reserve now');
    }

    public function test_owner_panel_pages_render(): void
    {
        $owner = $this->user('owner@turfbook.test');
        $turf = $owner->turfs()->first();
        foreach (['/panel', '/panel/schedule', '/panel/bookings', '/panel/turfs', '/panel/turfs/create', route('panel.turfs.edit', $turf), '/panel/staff', '/panel/reports', '/panel/verification'] as $url) {
            $this->actingAs($owner)->get($url)->assertOk();
        }
        $this->actingAs($owner)->get('/panel')->assertSee('Check these payments')->assertSee('My business');
    }

    public function test_staff_sees_daily_work_only(): void
    {
        $staff = $this->user('staff@turfbook.test');
        foreach (['/panel', '/panel/schedule', '/panel/bookings'] as $url) {
            $this->actingAs($staff)->get($url)->assertOk();
        }
        $this->actingAs($staff)->get('/panel')->assertDontSee('My business');
        $this->actingAs($staff)->get('/panel/turfs')->assertForbidden();
    }

    public function test_admin_pages_render(): void
    {
        $admin = $this->user('admin@turfbook.test');
        foreach (['/admin', '/admin/owners', '/admin/owners?status=all', '/admin/turfs', '/admin/turfs?status=pending', '/admin/users', '/admin/bookings', '/admin/settings'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
        $this->actingAs($admin)->get('/admin')->assertSee('Owners to verify')->assertSee('Turfs to approve');
    }

    public function test_turfs_without_photos_get_varied_illustrations(): void
    {
        $arts = Turf::all()->map->coverUrl()->unique();
        $this->assertGreaterThan(1, $arts->count());
        $this->assertTrue($arts->every(fn ($u) => str_contains($u, '/images/art/turf-')));
        foreach ($arts as $u) {
            $this->assertFileExists(public_path(parse_url($u, PHP_URL_PATH)));
        }
    }

    public function test_owner_can_upload_and_remove_gallery_photos(): void
    {
        Storage::fake('public');
        $owner = $this->user('owner@turfbook.test');
        $turf = $owner->turfs()->first();
        $fields = collect($turf->only([
            'name', 'description', 'address', 'area', 'city', 'phone', 'size', 'price_per_hour', 'night_price_per_hour',
            'slot_minutes', 'advance_percent', 'bkash_number', 'nagad_number',
        ]))->merge([
            'opens_at' => substr($turf->opens_at, 0, 5), 'closes_at' => substr($turf->closes_at, 0, 5),
            'night_starts_at' => substr($turf->night_starts_at, 0, 5), 'facilities' => $turf->facilities,
        ])->all();

        $this->actingAs($owner)->put(route('panel.turfs.update', $turf), $fields + [
            'cover' => UploadedFile::fake()->image('cover.jpg'),
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])->assertRedirect(route('panel.turfs.index'));

        $turf->refresh();
        $this->assertTrue($turf->hasOwnPhoto());
        $this->assertCount(2, $turf->gallery);
        $this->assertCount(3, $turf->photoUrls());
        Storage::disk('public')->assertExists($turf->gallery);
        $this->assertStringStartsWith('/storage/', $turf->coverUrl()); // works from any host (LAN IP, domain)

        $removed = $turf->gallery[0];
        $this->actingAs($owner)->put(route('panel.turfs.update', $turf), $fields + [
            'remove_photos' => [$removed, 'turfs/gallery/not-mine.jpg'],
        ])->assertRedirect();

        $turf->refresh();
        $this->assertCount(1, $turf->gallery);
        Storage::disk('public')->assertMissing($removed);
        $this->get(route('turfs.show', $turf))->assertOk()->assertSee('photo 2', false);
    }

    public function test_malformed_dates_in_links_do_not_crash_pages(): void
    {
        $turf = Turf::approved()->first();
        foreach (['garbage', '2026-13-45', '19/11/2026', ''] as $bad) {
            $this->get(route('turfs.show', [$turf, 'date' => $bad]))->assertOk();
            $this->get('/turfs?date='.urlencode($bad))->assertOk();
        }
        $owner = $this->user('owner@turfbook.test');
        $this->actingAs($owner)->get('/panel/schedule?date=garbage')->assertOk();
        $this->actingAs($owner)->get('/panel/bookings?date=nope')->assertOk();
        $this->actingAs($owner)->get('/panel/reports?month=13-2026')->assertOk();
        $this->actingAs($owner)->get('/panel/reports?month=2026-11')->assertOk()->assertSee('November 2026');
    }

    public function test_all_bookable_dates_are_reachable(): void
    {
        $turf = Turf::approved()->first();
        $max = (int) \App\Models\Setting::get('max_advance_booking_days');
        $last = today()->addDays($max);

        $this->get(route('turfs.show', $turf))->assertOk()->assertSee('Pick a date')
            ->assertSee('max="'.$last->toDateString().'"', false)
            ->assertSee(route('turfs.show', [$turf, 'date' => $last->toDateString()]), false)
            ->assertSee('up to <b>'.$last->format('d M').'</b>', false);

        // The last bookable day opens; one day past it falls back to today.
        $this->get(route('turfs.show', [$turf, 'date' => $last->toDateString()]))->assertSee($last->format('d F Y'));
        $this->get(route('turfs.show', [$turf, 'date' => $last->copy()->addDay()->toDateString()]))->assertSee('Today, '.today()->format('d F Y'));
    }
}
