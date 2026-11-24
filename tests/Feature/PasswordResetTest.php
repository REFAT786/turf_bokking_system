<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'customer', array $extra = []): User
    {
        return User::create($extra + ['name' => 'Tanvir', 'email' => 'tanvir@example.com', 'phone' => '01733333333', 'password' => 'oldpass1', 'role' => $role]);
    }

    public function test_login_page_links_to_forgot_password(): void
    {
        $this->get('/login')->assertSee(route('password.request'), false)->assertSee('Forgot password?');
        $this->get('/forgot-password')->assertOk()->assertSee('Send reset link');
    }

    public function test_reset_link_by_phone_or_email_and_same_reply_for_unknown(): void
    {
        Notification::fake();
        $u = $this->user();

        $this->post('/forgot-password', ['login' => '+880 1733-333333'])->assertSessionHas('status');
        Notification::assertSentTo($u, ResetPassword::class);

        $this->post('/forgot-password', ['login' => 'tanvir@example.com'])->assertSessionHas('status');

        // Unknown account → identical message, nothing sent.
        $this->post('/forgot-password', ['login' => '01799999999'])
            ->assertSessionHas('status', 'If an account matches, we have emailed a password reset link to its email address. It is valid for 60 minutes.');
        Notification::assertSentTimes(ResetPassword::class, 1); // second request within 60s is throttled by the broker
    }

    public function test_disabled_account_gets_no_link(): void
    {
        Notification::fake();
        $this->user('owner', ['is_active' => false]);
        $this->post('/forgot-password', ['login' => '01733333333'])->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_owner_resets_password_with_emailed_link(): void
    {
        $u = $this->user('owner');
        $token = Password::createToken($u);

        $this->get(route('password.reset', ['token' => $token, 'email' => $u->email]))->assertOk()->assertSee('Choose a new password');

        $this->post('/reset-password', ['token' => 'wrong', 'email' => $u->email, 'password' => 'newpass1', 'password_confirmation' => 'newpass1'])
            ->assertSessionHasErrors('email');

        $this->post('/reset-password', ['token' => $token, 'email' => $u->email, 'password' => 'newpass1', 'password_confirmation' => 'newpass1'])
            ->assertRedirect(route('login'));

        $this->post('/login', ['login' => '01733333333', 'password' => 'oldpass1'])->assertSessionHasErrors('login');
        $this->post('/login', ['login' => '01733333333', 'password' => 'newpass1'])->assertRedirect(route('panel.dashboard'));
        $this->post('/logout');

        // A used link can't be used again.
        $this->post('/reset-password', ['token' => $token, 'email' => $u->email, 'password' => 'another1', 'password_confirmation' => 'another1'])
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_set_a_users_password(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'a@t.test', 'phone' => '01700000000', 'password' => 'password', 'role' => 'super_admin']);
        $u = $this->user();

        $this->actingAs($admin)->get('/admin/users')->assertSee('Set password');
        $this->actingAs($admin)->post(route('admin.users.password', $u), ['password' => '123'])->assertSessionHasErrors('password');
        $this->actingAs($admin)->post(route('admin.users.password', $u), ['password' => 'support9'])->assertSessionHas('success');
        $this->actingAs($admin)->post(route('admin.users.password', $admin), ['password' => 'support9'])->assertStatus(422);

        $owner = $this->user('owner', ['email' => 'o@t.test', 'phone' => '01711111111']);
        $this->actingAs($owner)->post(route('admin.users.password', $u), ['password' => 'hacked99'])->assertForbidden();

        auth()->logout();
        $this->post('/login', ['login' => '01733333333', 'password' => 'support9'])->assertRedirect();
        $this->assertAuthenticatedAs($u->fresh());
    }
}
