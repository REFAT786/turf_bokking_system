<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteContentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'a@t.test', 'phone' => '01700000000', 'password' => 'password', 'role' => 'super_admin']);
    }

    public function test_defaults_show_on_the_website(): void
    {
        $this->get('/')->assertOk()->assertSee('Book your next game')->assertSee('Questions players ask')->assertSee('Help & contact', false)
            ->assertDontSee('announcement-bar');
    }

    public function test_every_section_editor_renders(): void
    {
        $admin = $this->admin();
        foreach (array_keys(SiteContent::SECTIONS) as $tab) {
            $this->actingAs($admin)->get(route('admin.content', ['tab' => $tab]))->assertOk()->assertSee(SiteContent::SECTIONS[$tab][0]);
        }
    }

    public function test_admin_edits_hero_text_and_image(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.content.save'), [
            'section' => 'hero',
            'hero_title' => 'Play tonight',
            'hero_highlight' => 'near you.',
            'hero_subtitle' => 'Deadline is {hours} hours.',
            'hero_search_placeholder' => 'Search…',
            'hero_image' => UploadedFile::fake()->image('hero.jpg', 1600, 900),
            'hero_show_stats' => '0',
        ])->assertRedirect(route('admin.content', ['tab' => 'hero']));

        $path = SiteContent::stored('hero.image');
        Storage::disk('public')->assertExists($path);
        $this->get('/')->assertSee('Play tonight')->assertSee('near you.')->assertSee('Deadline is 4 hours.')
            ->assertSee('/storage/'.$path, false)->assertDontSee('Games booked');

        // Replacing the image deletes the old file; reset brings back the default.
        $this->actingAs($admin)->post(route('admin.content.save'), ['section' => 'hero', 'hero_image_reset' => '1', 'hero_show_stats' => '1']);
        Storage::disk('public')->assertMissing($path);
        $this->get('/')->assertSee('images/art/hero.svg', false)->assertSee('Games booked');
    }

    public function test_announcement_bar_and_footer_contact(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.content.save'), [
            'section' => 'announcement', 'announcement_enabled' => '1', 'announcement_text' => 'Eid offer: 10% off', 'announcement_link' => '/turfs',
        ])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('admin.content.save'), [
            'section' => 'footer', 'footer_about' => 'About us', 'footer_phone' => '09610000000', 'footer_hours' => '24/7',
            'footer_email' => 'help@example.com', 'footer_facebook' => 'https://facebook.com/x', 'footer_copyright' => 'All rights reserved',
        ])->assertSessionHasNoErrors();

        $this->get('/how-it-works')->assertSee('Eid offer: 10% off')->assertSee('09610000000')->assertSee('help@example.com')
            ->assertSee('helpline')->assertSee('All rights reserved');

        $this->actingAs($admin)->post(route('admin.content.save'), ['section' => 'announcement', 'announcement_link' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('announcement_link');
    }

    public function test_faq_can_be_edited_and_emptied(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.content.save'), [
            'section' => 'faq', 'faq_title' => 'Need help?',
            'faq_items' => [['q' => 'Is parking free?', 'a' => 'Yes.'], ['q' => '', 'a' => 'dropped']],
        ]);
        $this->get('/')->assertSee('Need help?')->assertSee('Is parking free?')->assertDontSee('dropped')->assertDontSee('Do I pay online?');

        $this->actingAs($admin)->post(route('admin.content.save'), ['section' => 'faq', 'faq_title' => 'Need help?', 'faq_items' => []]);
        $this->get('/')->assertDontSee('Need help?');

        $this->actingAs($admin)->post(route('admin.content.reset', 'faq'));
        $this->get('/')->assertSee('Do I pay online?');
    }

    public function test_only_admin_can_edit_content(): void
    {
        $owner = User::create(['name' => 'O', 'email' => 'o@t.test', 'phone' => '01711111111', 'password' => 'password', 'role' => 'owner']);
        $this->actingAs($owner)->get(route('admin.content'))->assertForbidden();
        $this->actingAs($owner)->post(route('admin.content.save'), ['section' => 'hero', 'hero_title' => 'x'])->assertForbidden();
        auth()->logout();
        $this->post(route('admin.content.save'), ['section' => 'hero'])->assertRedirect(route('login'));
    }

    public function test_homepage_offer_shows_hides_and_expires(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $this->get('/')->assertSee('Why players book here')->assertDontSee('Eid special');

        $offer = fn (array $extra = []) => $this->actingAs($admin)->post(route('admin.content.save'), $extra + [
            'section' => 'offer', 'offer_enabled' => '1', 'offer_badge' => 'Eid offer', 'offer_title' => 'Eid special: 25% off',
            'offer_text' => 'All night slots.', 'offer_button' => 'Book now', 'offer_link' => '/turfs',
            'offer_ends_at' => now()->addDays(3)->toDateString(), 'offer_image' => UploadedFile::fake()->image('eid.jpg'),
        ]);

        $offer()->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Eid special: 25% off')->assertSee('Eid offer')->assertSee('Book now')
            ->assertSee('Ends '.now()->addDays(3)->format('d M Y'))->assertDontSee('Why players book here');

        // Last day still shows; the day after hides it automatically.
        $offer(['offer_ends_at' => now()->toDateString()]);
        $this->get('/')->assertSee('Eid special: 25% off');
        $offer(['offer_ends_at' => now()->subDay()->toDateString()]);
        $this->get('/')->assertDontSee('Eid special: 25% off')->assertSee('Why players book here');

        // Switched off → benefits card again.
        $offer(['offer_enabled' => '0', 'offer_ends_at' => '']);
        $this->get('/')->assertDontSee('Eid special: 25% off')->assertSee('Why players book here');

        $offer(['offer_ends_at' => '31-12-2026'])->assertSessionHasErrors('offer_ends_at');
    }
}
