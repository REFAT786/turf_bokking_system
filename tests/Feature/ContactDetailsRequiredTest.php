<?php

namespace Tests\Feature;

use App\Models\Turf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Every account needs BOTH a valid mobile (login) and a valid email (password reset) — on every form. */
class ContactDetailsRequiredTest extends TestCase
{
    use RefreshDatabase;

    private const BAD = [
        'missing both' => [['phone' => '', 'email' => ''], ['phone', 'email']],
        'missing email' => [['phone' => '01755555555', 'email' => ''], ['email']],
        'missing phone' => [['phone' => '', 'email' => 'new@example.com'], ['phone']],
        'email without domain' => [['phone' => '01755555555', 'email' => 'rahim@gmail'], ['email']],
        'not an email' => [['phone' => '01755555555', 'email' => 'rahim'], ['email']],
        'short phone' => [['phone' => '0175555', 'email' => 'new@example.com'], ['phone']],
    ];

    private function existing(): User
    {
        return User::create(['name' => 'Existing', 'email' => 'taken@example.com', 'phone' => '01799999999', 'password' => 'password', 'role' => 'customer']);
    }

    private function owner(): User
    {
        return User::create(['name' => 'Owner', 'email' => 'owner@example.com', 'phone' => '01711111111', 'password' => 'password', 'role' => 'owner', 'verification_status' => 'approved']);
    }

    /** @return array<string, callable(array): \Illuminate\Testing\TestResponse> */
    private function entryPoints(): array
    {
        Storage::fake('local');
        $owner = $this->owner();
        $turf = Turf::create(['owner_id' => $owner->id, 'name' => 'T', 'slug' => 't', 'area' => 'A', 'city' => 'Dhaka', 'address' => 'R',
            'phone' => '01711111111', 'price_per_hour' => 1000, 'bkash_number' => '01711111111', 'facilities' => [], 'status' => 'approved']);
        $staff = User::create(['name' => 'Staff', 'email' => 'staff@example.com', 'phone' => '01722222222', 'password' => 'password', 'role' => 'staff', 'owner_id' => $owner->id]);
        $profileUser = User::create(['name' => 'Me', 'email' => 'me@example.com', 'phone' => '01733333333', 'password' => 'password', 'role' => 'customer']);
        $pw = ['password' => 'secret123', 'password_confirmation' => 'secret123'];

        return [
            'player sign-up' => fn ($c) => $this->post('/register', $c + ['name' => 'P'] + $pw),
            'owner sign-up' => fn ($c) => $this->post('/register/owner', $c + ['name' => 'O', 'business_name' => 'B', 'nid_number' => '1234567890',
                'nid_front' => UploadedFile::fake()->image('f.jpg'), 'nid_back' => UploadedFile::fake()->image('b.jpg'),
                'selfie' => UploadedFile::fake()->image('s.jpg'), 'business_document' => UploadedFile::fake()->image('l.jpg')] + $pw),
            'staff create' => fn ($c) => $this->actingAs($owner)->post(route('panel.staff.store'), $c + ['name' => 'S', 'password' => 'secret123', 'turfs' => [$turf->id]]),
            'staff edit' => fn ($c) => $this->actingAs($owner)->put(route('panel.staff.update', $staff), $c + ['name' => 'S', 'turfs' => [$turf->id]]),
            'my profile' => fn ($c) => $this->actingAs($profileUser)->put('/profile', $c + ['name' => 'Me']),
        ];
    }

    public function test_every_form_requires_a_valid_mobile_and_email(): void
    {
        $this->existing();
        foreach ($this->entryPoints() as $form => $submit) {
            foreach (self::BAD as $case => [$contact, $errors]) {
                auth()->logout();
                $submit($contact)->assertSessionHasErrors($errors, null, 'default');
            }
            // Already used by another account — also when only the letter case or phone format differs.
            auth()->logout();
            $submit(['phone' => '+880 1799-999999', 'email' => 'TAKEN@Example.com '])->assertSessionHasErrors(['phone', 'email']);
        }
        $this->assertDatabaseCount('users', 4); // existing + owner + staff + profile user: nothing new was created
    }

    public function test_contact_details_are_stored_in_one_format_and_login_is_case_insensitive(): void
    {
        $this->post('/register', ['name' => 'Rahim', 'phone' => '+880 1766-666666', 'email' => '  Rahim.Uddin@Gmail.COM ', 'password' => 'secret123', 'password_confirmation' => 'secret123'])
            ->assertSessionHasNoErrors();
        $u = User::firstWhere('phone', '01766666666');
        $this->assertSame('rahim.uddin@gmail.com', $u->email);

        $this->post('/logout');
        $this->post('/login', ['login' => 'RAHIM.UDDIN@gmail.com', 'password' => 'secret123'])->assertRedirect();
        $this->assertAuthenticatedAs($u);

        // Even direct model writes are normalised.
        $u->update(['email' => 'NEW@Example.com', 'phone' => '8801766666667']);
        $this->assertSame(['new@example.com', '01766666667'], [$u->fresh()->email, $u->fresh()->phone]);
    }

    public function test_every_account_in_demo_data_has_both(): void
    {
        $this->seed();
        $this->assertSame(0, User::where(fn ($q) => $q->whereNull('email')->orWhere('email', '')->orWhereNull('phone')->orWhere('phone', ''))->count());
    }
}
