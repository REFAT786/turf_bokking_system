<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function player(): User
    {
        return User::firstWhere('email', 'player@turfbook.test');
    }

    public function test_every_role_can_open_their_profile(): void
    {
        foreach (['player', 'owner', 'staff', 'admin'] as $who) {
            $u = User::firstWhere('email', "$who@turfbook.test");
            $this->actingAs($u)->get('/profile')->assertOk()->assertSee($u->email)->assertSee('Change password');
        }
        $this->actingAs($this->player())->get('/profile')->assertSee('Games played')->assertSee('Player');
        $this->actingAs($this->player())->get('/')->assertSee(route('profile'), false);
        auth()->logout();
        $this->get('/profile')->assertRedirect(route('login'));
    }

    public function test_player_edits_details(): void
    {
        $p = $this->player();
        $this->actingAs($p)->put('/profile', ['name' => 'Tanvir Rahman', 'phone' => '+880 1766-666666', 'email' => 'tanvir@example.com'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $p->refresh();
        $this->assertSame(['Tanvir Rahman', '01766666666', 'tanvir@example.com'], [$p->name, $p->phone, $p->email]);

        // Taken phone/email are rejected; players can't set a business name.
        $this->actingAs($p)->put('/profile', ['name' => 'X', 'phone' => '01711111111', 'email' => 'owner@turfbook.test', 'business_name' => 'Fake Ltd'])
            ->assertSessionHasErrors(['phone', 'email', 'business_name']);
        $this->assertNull($p->fresh()->business_name);
    }

    public function test_owner_can_edit_business_name(): void
    {
        $o = User::firstWhere('email', 'owner@turfbook.test');
        $this->actingAs($o)->put('/profile', ['name' => $o->name, 'phone' => $o->phone, 'email' => $o->email, 'business_name' => 'Kickoff Group'])
            ->assertSessionHasNoErrors();
        $this->assertSame('Kickoff Group', $o->fresh()->business_name);
        $this->assertSame('owner', $o->fresh()->role);
    }

    public function test_password_change_needs_current_password(): void
    {
        $p = $this->player();

        $this->actingAs($p)->put('/profile/password', ['current_password' => 'wrong', 'password' => 'newpass1', 'password_confirmation' => 'newpass1'])
            ->assertSessionHasErrorsIn('password', ['current_password']);
        $this->actingAs($p)->put('/profile/password', ['current_password' => 'password', 'password' => 'newpass1', 'password_confirmation' => 'nope'])
            ->assertSessionHasErrorsIn('password', ['password']);
        $this->actingAs($p)->put('/profile/password', ['current_password' => 'password', 'password' => 'password', 'password_confirmation' => 'password'])
            ->assertSessionHasErrorsIn('password', ['password']);

        $this->actingAs($p)->put('/profile/password', ['current_password' => 'password', 'password' => 'newpass1', 'password_confirmation' => 'newpass1'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->post('/logout');
        $this->post('/login', ['login' => $p->phone, 'password' => 'password'])->assertSessionHasErrors('login');
        $this->post('/login', ['login' => $p->phone, 'password' => 'newpass1'])->assertRedirect();
        $this->assertAuthenticatedAs($p->fresh());
    }
}
