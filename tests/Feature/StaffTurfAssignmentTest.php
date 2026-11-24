<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Turf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffTurfAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private array $turfs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::create(['name' => 'Owner', 'email' => 'o@t.test', 'phone' => '01711111111', 'password' => 'password', 'role' => 'owner', 'verification_status' => 'approved']);
        foreach (['Alpha Arena', 'Beta Ground', 'Gamma Field'] as $i => $name) {
            $this->turfs[] = Turf::create([
                'owner_id' => $this->owner->id, 'name' => $name, 'slug' => 'turf-'.$i, 'area' => 'Area '.$i, 'city' => 'Dhaka',
                'address' => 'Road 1', 'phone' => '01711111111', 'price_per_hour' => 1500, 'bkash_number' => '01711111111',
                'facilities' => [], 'status' => 'approved', 'opens_at' => '06:00', 'closes_at' => '23:00',
            ]);
        }
    }

    private function createStaff(array $turfIds): User
    {
        $this->actingAs($this->owner)->post(route('panel.staff.store'), [
            'name' => 'Karim', 'phone' => '01722222222', 'email' => 'k@t.test', 'password' => 'secret123', 'turfs' => $turfIds,
        ])->assertSessionHasNoErrors();

        return User::firstWhere('email', 'k@t.test');
    }

    private function booking(Turf $turf, string $status = 'payment_submitted'): Booking
    {
        return Booking::create([
            'turf_id' => $turf->id, 'customer_name' => 'Player', 'customer_phone' => '01733333333',
            'starts_at' => now()->addDay()->setTime(19, 0), 'ends_at' => now()->addDay()->setTime(20, 0),
            'total_amount' => 1500, 'advance_amount' => 450, 'paid_amount' => 450, 'status' => $status, 'source' => 'online',
            'payment_method' => 'bkash', 'trx_id' => 'TRX'.$turf->id, 'payer_number' => '01733333333',
        ]);
    }

    public function test_staff_can_be_responsible_for_several_turfs_and_only_sees_those(): void
    {
        [$a, $b, $c] = $this->turfs;
        $staff = $this->createStaff([$a->id, $b->id]);

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $staff->managedTurfs()->pluck('id')->all());
        $this->assertTrue($staff->canManageTurf($a));
        $this->assertTrue($staff->canManageTurf($b));
        $this->assertFalse($staff->canManageTurf($c));

        $this->booking($a); $this->booking($c);
        $this->actingAs($staff)->get('/panel/bookings')->assertOk()->assertSee('TRX'.$a->id)->assertDontSee('TRX'.$c->id);
        $this->actingAs($staff)->get('/panel/schedule')->assertOk()->assertSee('Alpha Arena')->assertSee('Beta Ground')->assertDontSee('Gamma Field');
        // Asking for an unassigned turf's schedule falls back to an assigned one.
        $this->actingAs($staff)->get('/panel/schedule?turf='.$c->id)->assertOk()->assertDontSee('Gamma Field');
    }

    public function test_staff_cannot_act_on_unassigned_turf(): void
    {
        [$a, , $c] = $this->turfs;
        $staff = $this->createStaff([$a->id]);
        $other = $this->booking($c);

        $this->actingAs($staff)->post(route('panel.bookings.confirm', $other))->assertForbidden();
        $this->actingAs($staff)->post(route('panel.bookings.cancel', $other))->assertForbidden();
        $this->actingAs($staff)->post(route('panel.bookings.store'), [
            'turf_id' => $c->id, 'slots' => [now()->addDay()->setTime(10, 0)->toDateTimeString()], 'name' => 'Walk', 'phone' => '01744444444',
        ])->assertForbidden();

        $mine = $this->booking($a);
        $this->actingAs($staff)->post(route('panel.bookings.confirm', $mine))->assertRedirect();
        $this->assertSame('confirmed', $mine->fresh()->status);
    }

    public function test_owner_changes_assignments(): void
    {
        [$a, $b, $c] = $this->turfs;
        $staff = $this->createStaff([$a->id]);

        $this->actingAs($this->owner)->post(route('panel.staff.turfs', $staff), ['turfs' => [$b->id, $c->id]])->assertSessionHasNoErrors();
        $this->assertEqualsCanonicalizing([$b->id, $c->id], $staff->fresh()->managedTurfs()->pluck('id')->all());

        $this->actingAs($this->owner)->get('/panel/staff')->assertOk()->assertSee('Responsible for 2 turfs');
    }

    public function test_at_least_one_own_turf_is_required(): void
    {
        $this->actingAs($this->owner)->post(route('panel.staff.store'), [
            'name' => 'Karim', 'phone' => '01722222222', 'email' => 'k@t.test', 'password' => 'secret123', 'turfs' => [],
        ])->assertSessionHasErrors('turfs');

        $stranger = User::create(['name' => 'X', 'email' => 'x@t.test', 'phone' => '01755555555', 'password' => 'password', 'role' => 'owner']);
        $foreign = Turf::create([
            'owner_id' => $stranger->id, 'name' => 'Foreign', 'slug' => 'foreign', 'area' => 'Z', 'city' => 'Dhaka', 'address' => 'R',
            'phone' => '01755555555', 'price_per_hour' => 1000, 'bkash_number' => '01755555555', 'facilities' => [], 'status' => 'approved',
        ]);
        $this->actingAs($this->owner)->post(route('panel.staff.store'), [
            'name' => 'Karim', 'phone' => '01722222222', 'email' => 'k@t.test', 'password' => 'secret123', 'turfs' => [$foreign->id],
        ])->assertSessionHasErrors('turfs.0');
        $this->assertDatabaseMissing('users', ['email' => 'k@t.test']);

        // Another owner cannot change this owner's staff.
        $staff = $this->createStaff([$this->turfs[0]->id]);
        $this->actingAs($stranger)->post(route('panel.staff.turfs', $staff), ['turfs' => [$foreign->id]])->assertForbidden();
    }

    public function test_staff_without_turfs_is_sent_to_dashboard(): void
    {
        $staff = $this->createStaff([$this->turfs[0]->id]);
        $staff->assignedTurfs()->detach();

        $this->actingAs($staff)->get('/panel/schedule')->assertRedirect(route('panel.dashboard'));
        $this->actingAs($staff)->get('/panel')->assertOk()->assertSee("You haven't been given access to any turf yet", false);
    }

    public function test_owner_edits_staff_details_and_password(): void
    {
        [$a, $b] = $this->turfs;
        $staff = $this->createStaff([$a->id]);
        $oldHash = $staff->password;
        $this->actingAs($this->owner)->get('/panel/staff')->assertOk()->assertSee('Leave empty to keep current');

        // Details + turfs, password left empty → unchanged.
        $this->actingAs($this->owner)->put(route('panel.staff.update', $staff), [
            'name' => 'Karim Hossain', 'phone' => '+8801799999999', 'email' => 'karim.new@t.test', 'password' => '', 'turfs' => [$a->id, $b->id],
        ])->assertSessionHasNoErrors();
        $staff->refresh();
        $this->assertSame(['Karim Hossain', '01799999999', 'karim.new@t.test'], [$staff->name, $staff->phone, $staff->email]);
        $this->assertSame($oldHash, $staff->password);
        $this->assertCount(2, $staff->assignedTurfs);

        // New password works for logging in; the old one doesn't.
        $this->actingAs($this->owner)->put(route('panel.staff.update', $staff), [
            'name' => 'Karim Hossain', 'phone' => '01799999999', 'email' => 'karim.new@t.test', 'password' => 'newpass99', 'turfs' => [$a->id],
        ])->assertSessionHasNoErrors();
        auth()->logout();
        $this->post('/login', ['login' => '01799999999', 'password' => 'secret123'])->assertSessionHasErrors('login');
        $this->post('/login', ['login' => '01799999999', 'password' => 'newpass99'])->assertRedirect(route('panel.dashboard'));
        auth()->logout();

        // Phone/email already used by someone else → rejected; too-short password → rejected.
        $this->actingAs($this->owner)->put(route('panel.staff.update', $staff), [
            'name' => 'K', 'phone' => '01711111111', 'email' => 'o@t.test', 'password' => '123', 'turfs' => [$a->id],
        ])->assertSessionHasErrors(['phone', 'email', 'password']);

        // Another owner cannot edit this staff member.
        $stranger = User::create(['name' => 'X', 'email' => 'x@t.test', 'phone' => '01755555555', 'password' => 'password', 'role' => 'owner']);
        $this->actingAs($stranger)->put(route('panel.staff.update', $staff), [
            'name' => 'Hacked', 'phone' => '01799999999', 'email' => 'karim.new@t.test', 'turfs' => [$a->id],
        ])->assertForbidden();
        $this->assertSame('Karim Hossain', $staff->fresh()->name);
    }
}
