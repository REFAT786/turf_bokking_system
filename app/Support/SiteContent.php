<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Editable website content (texts, images, FAQ) managed from Admin → Website content.
 * Values live in the settings table under "content.<key>"; the defaults below are used until changed.
 *
 * Texts may contain placeholders: {hours} (payment deadline), {cancel_hours}, {turfs}, {areas}.
 */
class SiteContent
{
    public const IMAGE_DIR = 'site';

    /** Section => [title, icon, help, fields]. Field: key => [label, type, default, hint?]. */
    public const SECTIONS = [
        'announcement' => ['Announcement bar', 'bolt', 'A thin coloured bar at the very top of every public page. Good for offers or notices.', [
            'announcement.enabled' => ['Show the announcement bar', 'toggle', '0'],
            'announcement.text' => ['Message', 'text', '🎉 New turfs added in Uttara and Bashundhara — book your slot today!'],
            'announcement.link' => ['Link (optional)', 'url', '', 'Where the bar goes when clicked, e.g. /turfs'],
        ]],
        'brand' => ['Brand & SEO', 'star', 'Your site name and how it appears on Google and when shared.', [
            'brand.name_1' => ['Site name — first part', 'text', 'Turf'],
            'brand.name_2' => ['Site name — coloured part', 'text', 'Star'],
            'seo.title' => ['Browser tab title (home page)', 'text', 'Book football turfs near you'],
            'seo.description' => ['Search engine description', 'textarea', 'Find and book football turfs across Bangladesh. Live slots, instant reservation, pay the advance by bKash/Nagad.', 'Shown under your link on Google. About 150 characters.'],
        ]],
        'hero' => ['Homepage hero', 'home', 'The big first section visitors see on the homepage.', [
            'hero.title' => ['Headline', 'text', 'Book your next game'],
            'hero.highlight' => ['Headline — highlighted line', 'text', 'in 30 seconds.'],
            'hero.subtitle' => ['Sub-text', 'textarea', 'See which hours are free right now, reserve your slot and pay the advance with bKash or Nagad. No more calling five turfs.'],
            'hero.search_placeholder' => ['Search box hint', 'text', 'Area or turf name — Mirpur, Dhanmondi…'],
            'hero.image' => ['Background image', 'image', 'images/art/hero.svg', 'Wide photo, at least 1600×900.'],
            'hero.card_title' => ['Side card — title', 'text', 'Why players book here'],
            'hero.card_points' => ['Side card — points', 'lines', "See free hours at every turf, live\nReserve instantly — no phone calls\nPay the advance by bKash or Nagad\nEvery turf owner is verified", 'One per line (up to 6). Leave empty to hide the card.'],
            'hero.show_stats' => ['Show the numbers strip (turfs, areas, players, games)', 'toggle', '1'],
        ]],
        'offer' => ['Homepage offer', 'bolt', 'Show an offer or important notice in the card beside the homepage headline. When it is off (or has ended), the card shows “Why players book here” instead.', [
            'offer.enabled' => ['Show this offer on the homepage', 'toggle', '0'],
            'offer.badge' => ['Badge', 'text', 'Limited offer', 'Small label on top, e.g. “Eid offer”, “Notice”, “New”.'],
            'offer.title' => ['Title', 'text', '20% off night slots this week'],
            'offer.text' => ['Message', 'textarea', 'Book any slot after 8 PM and get 20% off. Valid at participating turfs.'],
            'offer.image' => ['Picture (optional)', 'image', '', 'Shown at the top of the card. Leave as is for no picture.'],
            'offer.button' => ['Button text (optional)', 'text', 'Find a turf'],
            'offer.link' => ['Button link', 'url', '/turfs', 'e.g. /turfs, /turfs/kickoff-arena or a full https:// link'],
            'offer.ends_at' => ['Hide automatically after (optional)', 'date', '', 'Last day the offer is shown. Leave empty to keep it until you switch it off.'],
        ]],
        'home' => ['Homepage sections', 'list', 'Headings and the 4 “how booking works” steps on the homepage.', [
            'home.popular_eyebrow' => ['Turfs section — small label', 'text', 'Ready to play'],
            'home.popular_title' => ['Turfs section — heading', 'text', 'Popular turfs'],
            'home.how_eyebrow' => ['Steps section — small label', 'text', 'Simple as 1-2-3-4'],
            'home.how_title' => ['Steps section — heading', 'text', 'How booking works'],
            'home.step1_title' => ['Step 1 — title', 'text', 'Find a turf'],
            'home.step1_text' => ['Step 1 — text', 'text', 'Search by area or tap “Turfs near me”.'],
            'home.step2_title' => ['Step 2 — title', 'text', 'Pick a free slot'],
            'home.step2_text' => ['Step 2 — text', 'text', 'Green slots are free. Tap one or more hours.'],
            'home.step3_title' => ['Step 3 — title', 'text', 'Pay the advance'],
            'home.step3_text' => ['Step 3 — text', 'text', 'Send it by bKash/Nagad and enter the TrxID.'],
            'home.step4_title' => ['Step 4 — title', 'text', 'Show up & play'],
            'home.step4_text' => ['Step 4 — text', 'text', 'Show your booking code, pay the rest at the turf.'],
            'home.areas_title' => ['Areas section — heading', 'text', 'Browse by area'],
        ]],
        'cta' => ['Owner banner', 'pitch', 'The “For turf owners” banner near the bottom of the homepage.', [
            'cta.eyebrow' => ['Small label', 'text', 'For turf owners'],
            'cta.title' => ['Heading', 'text', 'Fill every hour of your turf.'],
            'cta.text' => ['Text', 'textarea', 'A free dashboard to manage your schedule, check bKash/Nagad advances, add walk-ins, give staff access and see monthly income. Money goes straight to your own account.'],
            'cta.points' => ['Bullet points', 'lines', "Unpaid holds auto-cancel\nDay & night pricing\nStaff logins\nMonthly income report", 'One per line.'],
            'cta.button' => ['Button text', 'text', "List your turf — it's free"],
            'cta.image' => ['Image', 'image', 'images/art/turf-2.svg'],
        ]],
        'faq' => ['FAQ', 'info', 'Questions & answers shown on the homepage. Leave empty to hide the section.', [
            'faq.title' => ['Heading', 'text', 'Questions players ask'],
            'faq.items' => ['Questions', 'faq', [
                ['q' => 'Do I pay online?', 'a' => "You pay a small advance straight to the turf's bKash/Nagad number and enter the TrxID. The rest is paid at the turf."],
                ['q' => 'What if I don\'t pay the advance?', 'a' => 'Your booking is held for {hours} hours. If no payment is submitted by then, it is cancelled automatically and the slot is released.'],
                ['q' => 'Can I cancel my booking?', 'a' => 'Yes — online, up to {cancel_hours} hours before the game. Refunds of advances are handled by the turf.'],
                ['q' => 'Are the turfs verified?', 'a' => 'Every turf owner is checked by our team (NID, selfie and business proof) before their turf goes live.'],
            ]],
        ]],
        'how' => ['How it works page', 'doc', 'The header of the “How it works” page.', [
            'how.title' => ['Heading', 'text', 'How booking works'],
            'how.intro' => ['Intro', 'textarea', 'Five simple steps from finding a turf to kicking off. No phone calls needed.'],
            'how.image' => ['Header image', 'image', 'images/art/turf-7.svg'],
        ]],
        'footer' => ['Footer & contact', 'phone', 'The dark section at the bottom of every page.', [
            'footer.about' => ['About text', 'textarea', 'Book football turfs across Bangladesh in seconds. Live availability, instant reservation and the advance paid straight to the turf.'],
            'footer.phone' => ['Support phone', 'text', '01700000000', 'Your platform helpline — shown as “Help & contact”, not as an owner number.'],
            'footer.hours' => ['Support hours', 'text', 'Every day, 10 AM – 10 PM'],
            'footer.email' => ['Support email (optional)', 'email', ''],
            'footer.facebook' => ['Facebook page link (optional)', 'url', ''],
            'footer.copyright' => ['Bottom line', 'text', 'Made for football in Bangladesh ⚽'],
        ]],
    ];

    /** Field definition for a key. */
    public static function field(string $key): ?array
    {
        foreach (self::SECTIONS as [, , , $fields]) {
            if (isset($fields[$key])) {
                return $fields[$key];
            }
        }

        return null;
    }

    /** Raw stored value (string), or null if never changed. */
    public static function stored(string $key): ?string
    {
        return Setting::get('content.'.$key);
    }

    /** Text value with placeholders filled in. */
    public static function text(string $key): string
    {
        return self::fill((string) (self::stored($key) ?? self::field($key)[2] ?? ''));
    }

    public static function on(string $key): bool
    {
        return (self::stored($key) ?? self::field($key)[2] ?? '0') === '1';
    }

    /** Non-empty lines of a "lines" field. */
    public static function lines(string $key): array
    {
        return array_values(array_filter(array_map('trim', explode("\n", self::text($key)))));
    }

    /** Image URL: uploaded file if any, else the built-in default. */
    public static function image(string $key): string
    {
        $path = self::stored($key);
        $default = self::field($key)[2] ?? '';

        return $path ? Storage::disk('public')->url($path) : ($default ? asset($default) : '');
    }

    public static function hasCustomImage(string $key): bool
    {
        return (bool) self::stored($key);
    }

    /** Is the homepage offer switched on and not past its end date? */
    public static function offerActive(): bool
    {
        if (! self::on('offer.enabled') || self::text('offer.title') === '') {
            return false;
        }
        $ends = self::stored('offer.ends_at');

        return ! $ends || now()->startOfDay()->lte(\Carbon\Carbon::parse($ends));
    }

    /** @return array<int, array{q: string, a: string}> */
    public static function faq(): array
    {
        $raw = self::stored('faq.items');
        $items = $raw !== null ? (json_decode($raw, true) ?: []) : self::field('faq.items')[2];

        return array_map(fn ($i) => ['q' => self::fill($i['q']), 'a' => self::fill($i['a'])], $items);
    }

    /** Raw FAQ items for the editor (placeholders not filled). */
    public static function faqRaw(): array
    {
        $raw = self::stored('faq.items');

        return $raw !== null ? (json_decode($raw, true) ?: []) : self::field('faq.items')[2];
    }

    public static function fill(string $text): string
    {
        static $vars;
        $vars ??= [
            '{hours}' => Setting::get('payment_deadline_hours'),
            '{cancel_hours}' => Setting::get('min_hours_before_cancel'),
            '{turfs}' => fn () => \App\Models\Turf::approved()->count(),
            '{areas}' => fn () => \App\Models\Turf::approved()->distinct()->count('area'),
        ];
        foreach ($vars as $k => $v) {
            if (str_contains($text, $k)) {
                $text = str_replace($k, (string) (is_callable($v) ? $v() : $v), $text);
            }
        }

        return $text;
    }
}
