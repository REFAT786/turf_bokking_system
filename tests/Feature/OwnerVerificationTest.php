<?php

namespace Tests\Feature;

use App\Models\Turf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OwnerVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function ownerSignup(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rahim Uddin',
            'business_name' => 'Kickoff Sports Ltd',
            'phone' => '01755555555',
            'email' => 'rahim@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'nid_number' => '1234567890',
            'nid_front' => UploadedFile::fake()->image('front.jpg'),
            'nid_back' => UploadedFile::fake()->image('back.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
            'business_document' => UploadedFile::fake()->create('license.pdf', 200, 'application/pdf'),
        ], $overrides);
    }

    private function makeTurf(User $owner, string $status = 'approved'): Turf
    {
        return Turf::create([
            'owner_id' => $owner->id, 'name' => 'Test Arena', 'slug' => 'test-arena-'.$owner->id, 'area' => 'Mirpur', 'city' => 'Dhaka',
            'address' => 'Road 1', 'phone' => '01711111111', 'price_per_hour' => 1500, 'bkash_number' => '01711111111',
            'facilities' => [], 'status' => $status,
        ]);
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@t.test', 'phone' => '01700000000', 'password' => 'password', 'role' => 'super_admin']);
    }

    public function test_player_signup_needs_no_documents(): void
    {
        $this->post('/register', [
            'name' => 'Tanvir', 'phone' => '01733333333', 'email' => 'p@example.com',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertRedirect();

        $user = User::firstWhere('email', 'p@example.com');
        $this->assertSame('customer', $user->role);
        $this->assertNull($user->verification_status);
    }

    public function test_owner_signup_requires_documents(): void
    {
        $this->post('/register/owner', $this->ownerSignup(['nid_front' => null, 'business_document' => null, 'nid_number' => '123']))
            ->assertSessionHasErrors(['nid_front', 'business_document', 'nid_number']);

        $this->assertDatabaseMissing('users', ['email' => 'rahim@example.com']);
    }

    public function test_owner_signup_stores_documents_privately_and_is_pending(): void
    {
        $this->post('/register/owner', $this->ownerSignup())->assertRedirect(route('panel.turfs.create'));

        $owner = User::firstWhere('email', 'rahim@example.com');
        $this->assertSame('owner', $owner->role);
        $this->assertSame(User::VERIFICATION_PENDING, $owner->verification_status);
        $this->assertNotNull($owner->verification_submitted_at);
        foreach (['nid_front_path', 'nid_back_path', 'selfie_path', 'business_document_path'] as $col) {
            Storage::disk('local')->assertExists($owner->$col);
        }
    }

    public function test_unverified_owners_turf_is_hidden_and_not_bookable(): void
    {
        $this->post('/register/owner', $this->ownerSignup());
        $owner = User::firstWhere('email', 'rahim@example.com');
        $turf = $this->makeTurf($owner, 'approved'); // even an approved turf stays hidden

        $this->assertFalse($turf->isLive());
        $this->assertSame(0, Turf::approved()->count());
        auth()->logout();
        $this->get(route('turfs.show', $turf))->assertNotFound();

        $player = User::create(['name' => 'P', 'email' => 'pl@t.test', 'phone' => '01733333333', 'password' => 'password', 'role' => 'customer']);
        $this->actingAs($player)->post(route('turfs.book', $turf), ['slots' => [now()->addDay()->setTime(19, 0)->toDateTimeString()]])
            ->assertNotFound();

        // The owner can still preview and manage it.
        $this->actingAs($owner)->get(route('turfs.show', $turf))->assertOk()->assertSee('Preview only')->assertSee("owner's account is verified");
        $this->actingAs($owner)->get(route('panel.dashboard'))->assertOk()->assertSee('Account under review');
    }

    public function test_admin_can_view_documents_and_approve_owner(): void
    {
        $this->post('/register/owner', $this->ownerSignup());
        $owner = User::firstWhere('email', 'rahim@example.com');
        $turf = $this->makeTurf($owner);
        auth()->logout();

        // Documents are not reachable by the owner, players or guests.
        $this->get(route('admin.owners.document', [$owner, 'nid_front']))->assertRedirect(route('login'));
        $this->actingAs($owner)->get(route('admin.owners.document', [$owner, 'nid_front']))->assertForbidden();

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.owners'))->assertOk()->assertSee('Kickoff Sports Ltd')->assertSee('1234567890');
        $this->actingAs($admin)->get(route('admin.owners.document', [$owner, 'nid_front']))->assertOk();
        $this->actingAs($admin)->get(route('admin.owners.document', [$owner, 'selfie']))->assertOk();
        $this->actingAs($admin)->get(route('admin.owners'))->assertSee('Face match')->assertSee(route('admin.owners.document', [$owner, 'selfie']), false);
        $this->actingAs($admin)->get(route('admin.owners.document', [$owner, 'nope']))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Owners to verify');

        $this->actingAs($admin)->post(route('admin.owners.approve', $owner))->assertRedirect();

        $owner->refresh();
        $this->assertTrue($owner->isVerifiedOwner());
        $this->assertSame($admin->id, $owner->verified_by);
        $this->assertTrue($turf->fresh()->isLive());
        $this->assertSame(1, Turf::approved()->count());
    }

    public function test_rejected_owner_sees_reason_and_can_resubmit(): void
    {
        $this->post('/register/owner', $this->ownerSignup());
        $owner = User::firstWhere('email', 'rahim@example.com');
        $oldFront = $owner->nid_front_path;
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.owners.reject', $owner), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAs($admin)->post(route('admin.owners.reject', $owner), ['reason' => 'NID photo is blurry'])->assertRedirect();
        $this->assertSame(User::VERIFICATION_REJECTED, $owner->fresh()->verification_status);

        $this->actingAs($owner->fresh())->get(route('panel.verification'))->assertOk()->assertSee('NID photo is blurry');

        $this->actingAs($owner->fresh())->post(route('panel.verification.store'), [
            'nid_number' => '1234567890123',
            'nid_front' => UploadedFile::fake()->image('front2.jpg'),
            'nid_back' => UploadedFile::fake()->image('back2.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie2.jpg'),
            'business_document' => UploadedFile::fake()->image('license2.png'),
        ])->assertRedirect(route('panel.verification'));

        $owner->refresh();
        $this->assertSame(User::VERIFICATION_PENDING, $owner->verification_status);
        $this->assertNull($owner->verification_note);
        Storage::disk('local')->assertMissing($oldFront);
        Storage::disk('local')->assertExists($owner->nid_front_path);
    }

    public function test_admin_can_revoke_a_verified_owner(): void
    {
        $owner = User::create(['name' => 'O', 'email' => 'o@t.test', 'phone' => '01711111111', 'password' => 'password',
            'role' => 'owner', 'verification_status' => 'approved']);
        $turf = $this->makeTurf($owner);
        $this->assertTrue($turf->isLive());

        $this->actingAs($this->admin())->post(route('admin.owners.reject', $owner), ['reason' => 'Fake license reported'])->assertRedirect();

        $this->assertFalse($turf->fresh()->isLive());
        $this->assertSame(0, Turf::approved()->count());
    }

    public function test_only_admin_reaches_owner_verification_pages(): void
    {
        $owner = User::create(['name' => 'O', 'email' => 'o@t.test', 'phone' => '01711111111', 'password' => 'password', 'role' => 'owner']);
        $this->actingAs($owner)->get(route('admin.owners'))->assertForbidden();
        $this->actingAs($owner)->post(route('admin.owners.approve', $owner))->assertForbidden();
    }

    public function test_selfie_is_required_and_must_be_a_photo(): void
    {
        $this->post('/register/owner', $this->ownerSignup(['selfie' => null]))->assertSessionHasErrors('selfie');
        $this->post('/register/owner', $this->ownerSignup(['selfie' => UploadedFile::fake()->create('me.pdf', 100, 'application/pdf')]))
            ->assertSessionHasErrors('selfie');
        $this->assertDatabaseMissing('users', ['email' => 'rahim@example.com']);
    }
}
