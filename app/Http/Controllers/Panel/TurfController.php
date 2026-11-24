<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Turf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TurfController extends Controller
{
    public function index(Request $request)
    {
        $turfs = $request->user()->turfs()->withCount('bookings')->latest()->get();

        return view('panel.turfs.index', compact('turfs'));
    }

    public function create()
    {
        return view('panel.turfs.form', ['turf' => new Turf([
            'opens_at' => '06:00', 'closes_at' => '00:00', 'slot_minutes' => 60, 'advance_percent' => 30, 'city' => 'Dhaka',
        ])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['owner_id'] = $request->user()->id;
        $data['slug'] = $this->uniqueSlug($data['name'].' '.$data['area']);
        $data['status'] = Setting::get('auto_approve_turfs') === '1' ? 'approved' : 'pending';

        $turf = Turf::create($data);

        $msg = match (true) {
            $turf->isLive() => 'Turf published! Customers can book it now.',
            ! $request->user()->isVerifiedOwner() => 'Turf saved. It will go live once the admin verifies your account'.($turf->status === 'approved' ? '.' : ' and approves the turf.'),
            default => 'Turf submitted. It will appear publicly once the admin approves it.',
        };

        if ($request->user()->staff()->exists()) {
            $msg .= ' Want your staff to handle it too? Tick it for them on the Staff page.';
        }

        return redirect()->route('panel.turfs.index')->with('success', $msg);
    }

    public function edit(Request $request, Turf $turf)
    {
        abort_unless($turf->owner_id === $request->user()->id, 403);

        return view('panel.turfs.form', compact('turf'));
    }

    public function update(Request $request, Turf $turf)
    {
        abort_unless($turf->owner_id === $request->user()->id, 403);
        $data = $this->validated($request, $turf);
        $turf->update($data);

        return redirect()->route('panel.turfs.index')->with('success', 'Turf updated.');
    }

    private function validated(Request $request, ?Turf $turf = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:3000'],
            'address' => ['required', 'string', 'max:255'],
            'area' => ['required', 'string', 'max:80'],
            'city' => ['required', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'size' => ['nullable', 'string', 'max:50'],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['in:'.implode(',', array_keys(Turf::FACILITIES))],
            'cover' => ['nullable', 'image', 'max:3072'],
            'photos' => ['nullable', 'array', 'max:'.Turf::MAX_GALLERY],
            'photos.*' => ['image', 'max:3072'],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['string'],
            'price_per_hour' => ['required', 'integer', 'min:100', 'max:100000'],
            'night_price_per_hour' => ['nullable', 'integer', 'min:100', 'max:100000'],
            'night_starts_at' => ['nullable', 'required_with:night_price_per_hour', 'date_format:H:i'],
            'opens_at' => ['required', 'date_format:H:i'],
            'closes_at' => ['required', 'date_format:H:i'],
            'slot_minutes' => ['required', 'in:60,90,120'],
            'advance_percent' => ['required', 'integer', 'between:10,100'],
            'bkash_number' => ['nullable', 'regex:/^01[3-9]\d{8}$/'],
            'nagad_number' => ['nullable', 'regex:/^01[3-9]\d{8}$/'],
            'rocket_number' => ['nullable', 'regex:/^01[3-9]\d{8,9}$/'],
            'payment_note' => ['nullable', 'string', 'max:500'],
        ]);

        if (empty($data['bkash_number']) && empty($data['nagad_number']) && empty($data['rocket_number'])) {
            abort(back()->withInput()->withErrors(['bkash_number' => 'Add at least one bKash, Nagad or Rocket number to receive advance payments.']));
        }

        if ($request->hasFile('cover')) {
            if ($turf?->cover_image) {
                Storage::disk('public')->delete($turf->cover_image);
            }
            $data['cover_image'] = $request->file('cover')->store('turfs', 'public');
        }
        unset($data['cover']);

        // Gallery: drop removed photos (only ones that belong to this turf), then append new uploads.
        $gallery = $turf?->gallery ?? [];
        $remove = array_intersect($data['remove_photos'] ?? [], $gallery);
        Storage::disk('public')->delete($remove);
        $gallery = array_values(array_diff($gallery, $remove));
        foreach ($request->file('photos', []) as $photo) {
            if (count($gallery) >= Turf::MAX_GALLERY) {
                break;
            }
            $gallery[] = $photo->store('turfs/gallery', 'public');
        }
        $data['gallery'] = $gallery;
        unset($data['photos'], $data['remove_photos']);
        $data['facilities'] = $data['facilities'] ?? [];

        return $data;
    }

    private function uniqueSlug(string $text): string
    {
        $base = Str::slug($text) ?: 'turf';
        $slug = $base;
        $i = 2;
        while (Turf::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
