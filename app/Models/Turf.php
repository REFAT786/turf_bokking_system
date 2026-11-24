<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Turf extends Model
{
    public const FACILITIES = [
        'parking' => 'Parking',
        'changing_room' => 'Changing room',
        'washroom' => 'Washroom',
        'drinking_water' => 'Drinking water',
        'floodlights' => 'Floodlights',
        'ball_provided' => 'Ball provided',
        'bibs' => 'Bibs / jerseys',
        'cafeteria' => 'Cafeteria',
        'prayer_room' => 'Prayer room',
        'rooftop' => 'Rooftop',
        'indoor' => 'Indoor / covered',
        'first_aid' => 'First aid',
    ];

    protected $fillable = [
        'owner_id', 'name', 'slug', 'description', 'address', 'area', 'city', 'latitude', 'longitude',
        'phone', 'size', 'facilities', 'cover_image', 'gallery', 'price_per_hour', 'night_price_per_hour',
        'night_starts_at', 'opens_at', 'closes_at', 'slot_minutes', 'advance_percent',
        'bkash_number', 'nagad_number', 'rocket_number', 'payment_note', 'status',
    ];

    protected function casts(): array
    {
        return [
            'facilities' => 'array',
            'gallery' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo { return $this->belongsTo(User::class, 'owner_id'); }
    public function bookings(): HasMany { return $this->hasMany(Booking::class); }
    public function staff(): \Illuminate\Database\Eloquent\Relations\BelongsToMany { return $this->belongsToMany(User::class, 'turf_staff')->withTimestamps(); }
    public function reviews(): HasMany { return $this->hasMany(Review::class); }

    public function scopeApproved(Builder $q): Builder
    {
        // Publicly visible: the turf is approved AND its owner's identity has been verified.
        return $q->where('status', 'approved')
            ->whereHas('owner', fn ($o) => $o->where('verification_status', User::VERIFICATION_APPROVED)->where('is_active', true));
    }

    /** Adds a `distance_km` column (Haversine) and orders by it. */
    /** Can players see and book this turf? */
    public function isLive(): bool
    {
        return $this->status === 'approved' && $this->owner?->isVerifiedOwner() && $this->owner->is_active;
    }

    public function scopeNearest(Builder $q, float $lat, float $lng): Builder
    {
        if (is_null($q->getQuery()->columns)) {
            $q->select('turfs.*');
        }

        return $q->whereNotNull('latitude')
            ->selectRaw('(6371 * acos(least(1, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))) as distance_km', [$lat, $lng, $lat])
            ->orderBy('distance_km');
    }

    public function coverUrl(): string
    {
        return $this->cover_image ? Storage::url($this->cover_image) : $this->artUrl();
    }

    /** Built-in illustration used until the owner uploads a photo — varies per turf so listings don't look identical. */
    public function artUrl(): string
    {
        return asset('images/art/turf-'.((($this->id ?: crc32((string) $this->name)) % self::ART_VARIANTS) + 1).'.svg');
    }

    public const ART_VARIANTS = 8;
    public const MAX_GALLERY = 8;

    /** Cover first, then gallery photos. Always at least one image. */
    public function photoUrls(): array
    {
        return array_values(array_merge(
            [$this->coverUrl()],
            array_map(fn ($p) => Storage::url($p), $this->gallery ?? []),
        ));
    }

    public function hasOwnPhoto(): bool
    {
        return (bool) $this->cover_image;
    }

    /**
     * Plain-language visibility for owners and admins.
     *
     * @return array{label: string, color: string, hint: string}
     */
    public function publicState(): array
    {
        return match (true) {
            $this->isLive() => ['label' => 'Live', 'color' => 'bg-emerald-100 text-emerald-800', 'hint' => 'Players can find and book it.'],
            $this->status === 'suspended' => ['label' => 'Suspended', 'color' => 'bg-rose-100 text-rose-700', 'hint' => 'Hidden by the admin. Contact support.'],
            ! $this->owner?->isVerifiedOwner() => ['label' => 'Hidden', 'color' => 'bg-amber-100 text-amber-800', 'hint' => "Goes live once the owner's account is verified."],
            default => ['label' => 'Waiting for approval', 'color' => 'bg-amber-100 text-amber-800', 'hint' => 'The admin will review it shortly.'],
        };
    }

    public function paymentAccounts(): array
    {
        return array_filter([
            'bkash' => $this->bkash_number,
            'nagad' => $this->nagad_number,
            'rocket' => $this->rocket_number,
        ]);
    }

    public function whatsappLink(string $text = ''): string
    {
        $num = preg_replace('/\D/', '', $this->phone);
        if (str_starts_with($num, '0')) {
            $num = '88'.$num;
        }

        return 'https://wa.me/'.$num.($text ? '?text='.rawurlencode($text) : '');
    }

    public function minPrice(): int
    {
        return min(array_filter([$this->price_per_hour, $this->night_price_per_hour]));
    }

    /** Is the given slot start inside "night" pricing? */
    public function isNightSlot(CarbonInterface $start): bool
    {
        if (! $this->night_price_per_hour || ! $this->night_starts_at) {
            return false;
        }
        $t = $start->format('H:i:s');

        // Night runs from night_starts_at until the turf opens again (crossing midnight).
        return $t >= $this->night_starts_at || $t < $this->opens_at;
    }

    public function priceForSlot(CarbonInterface $start): int
    {
        $hourly = $this->isNightSlot($start) ? $this->night_price_per_hour : $this->price_per_hour;

        return (int) round($hourly * $this->slot_minutes / 60);
    }

    /**
     * All slots for the "business day" that starts on $date.
     * A turf that opens 06:00 and closes 02:00 produces slots up to 02:00 the next day.
     *
     * @return array<int, array{start: Carbon, end: Carbon, price: int, status: string, night: bool}>
     */
    public function slotsFor(Carbon $date): array
    {
        $open = $date->copy()->setTimeFromTimeString($this->opens_at);
        $close = $date->copy()->setTimeFromTimeString($this->closes_at);
        if ($close->lessThanOrEqualTo($open)) {
            $close->addDay();
        }

        $taken = $this->bookings()
            ->blocking()
            ->where('starts_at', '<', $close)
            ->where('ends_at', '>', $open)
            ->get(['starts_at', 'ends_at', 'status']);

        $now = now();
        $slots = [];
        for ($s = $open->copy(); $s->copy()->addMinutes($this->slot_minutes)->lessThanOrEqualTo($close); $s->addMinutes($this->slot_minutes)) {
            $e = $s->copy()->addMinutes($this->slot_minutes);
            $status = 'available';
            if ($s->lessThan($now)) {
                $status = 'past';
            } else {
                foreach ($taken as $b) {
                    if ($b->starts_at->lessThan($e) && $b->ends_at->greaterThan($s)) {
                        $status = $b->status === 'pending_payment' ? 'held' : 'booked';
                        break;
                    }
                }
            }
            $slots[] = [
                'start' => $s->copy(),
                'end' => $e,
                'price' => $this->priceForSlot($s),
                'status' => $status,
                'night' => $this->isNightSlot($s),
            ];
        }

        return $slots;
    }
}
