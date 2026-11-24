@extends('layouts.panel')
@section('title', $turf->exists ? 'Edit '.$turf->name : 'Add a turf')
@section('subtitle', 'Fill in each section. Fields marked * are required.')
@include('partials.leaflet')
@section('content')
@php($t = fn ($v) => $v ? substr($v, 0, 5) : null)
<form method="POST" enctype="multipart/form-data" action="{{ $turf->exists ? route('panel.turfs.update', $turf) : route('panel.turfs.store') }}" class="mx-auto max-w-4xl space-y-6" x-data>
    @csrf
    @if ($turf->exists) @method('PUT') @endif

    <div class="card p-6">
        <h2 class="flex items-center gap-3 text-lg font-semibold"><span class="grid h-7 w-7 place-items-center rounded-full bg-brand-600 text-sm text-white">1</span> Basic info</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div><label class="label">Turf name *</label><input name="name" value="{{ old('name', $turf->name) }}" class="input" required></div>
            <div><label class="label">Contact phone *</label><input name="phone" value="{{ old('phone', $turf->phone ?? auth()->user()->phone) }}" class="input" required></div>
            <div><label class="label">Size / format</label><input name="size" value="{{ old('size', $turf->size) }}" class="input" placeholder="e.g. 7-a-side, 60×40 ft"></div>
            <div class="md:col-span-2"><label class="label">Description</label><textarea name="description" rows="3" class="input" placeholder="Surface, lights, rules…">{{ old('description', $turf->description) }}</textarea></div>
        </div>
        <p class="label mt-4">Facilities</p>
        <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
            @foreach (\App\Models\Turf::FACILITIES as $k => $label)
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="facilities[]" value="{{ $k }}" class="rounded" @checked(in_array($k, old('facilities', $turf->facilities ?? [])))> {{ $label }}</label>
            @endforeach
        </div>
    </div>

    <div class="card p-6" id="photos">
        <h2 class="flex items-center gap-3 text-lg font-semibold"><span class="grid h-7 w-7 place-items-center rounded-full bg-brand-600 text-sm text-white">2</span> Photos</h2>
        <p class="mt-1 text-sm text-slate-500">Turfs with real photos get far more bookings. Add a clear cover photo and a few more of the pitch, lights, changing room and parking. JPG/PNG up to 3 MB each.</p>

        <div class="mt-5 grid gap-6 md:grid-cols-5">
            <div class="md:col-span-2" x-data="{ preview: @js($turf->coverUrl()), own: @js($turf->hasOwnPhoto()) }">
                <p class="label">Cover photo</p>
                <label class="group relative block cursor-pointer overflow-hidden rounded-2xl ring-1 ring-slate-200">
                    <img :src="preview" alt="" class="aspect-[16/10] w-full object-cover">
                    <span class="absolute inset-0 grid place-items-center bg-black/40 text-sm font-semibold text-white opacity-0 transition group-hover:opacity-100" :class="!own && '!opacity-100 !bg-black/45'">
                        <span class="flex items-center gap-2"><x-icon name="image"/> <span x-text="own ? 'Change cover' : 'Upload cover photo'"></span></span>
                    </span>
                    <input type="file" name="cover" accept="image/*" class="sr-only" @change="const f = $event.target.files[0]; if (f) { preview = URL.createObjectURL(f); own = true }">
                </label>
                <p class="hint" x-show="!own">Showing a placeholder illustration until you upload a photo.</p>
            </div>

            <div class="md:col-span-3" x-data="{ added: [] }">
                <p class="label">More photos <span class="font-normal text-slate-400">(up to {{ \App\Models\Turf::MAX_GALLERY }})</span></p>
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($turf->gallery ?? [] as $path)
                        <label class="group relative block overflow-hidden rounded-xl ring-1 ring-slate-200" x-data="{ del: false }">
                            <img src="{{ Storage::url($path) }}" alt="" class="aspect-square w-full object-cover transition" :class="del && 'opacity-30 grayscale'">
                            <input type="checkbox" name="remove_photos[]" value="{{ $path }}" class="sr-only" x-model="del">
                            <span class="absolute right-1.5 top-1.5 grid h-7 w-7 cursor-pointer place-items-center rounded-full shadow" :class="del ? 'bg-brand-600 text-white' : 'bg-white/95 text-rose-600'" :title="del ? 'Keep photo' : 'Remove photo'">
                                <x-icon name="trash" class="h-4 w-4" x-show="!del"/><x-icon name="arrow-left" class="h-4 w-4" x-show="del" x-cloak/>
                            </span>
                            <span x-show="del" x-cloak class="absolute inset-x-0 bottom-0 bg-rose-600 py-0.5 text-center text-[11px] font-semibold text-white">Will be removed</span>
                        </label>
                    @endforeach
                    <template x-for="src in added"><img :src="src" alt="" class="aspect-square w-full rounded-xl object-cover ring-2 ring-brand-400"></template>
                    <label class="grid aspect-square cursor-pointer place-items-center rounded-xl border-2 border-dashed border-slate-300 text-center text-xs font-semibold text-slate-500 transition hover:border-brand-400 hover:text-brand-700">
                        <span><x-icon name="plus" class="mx-auto h-6 w-6"/> Add photos</span>
                        <input type="file" name="photos[]" accept="image/*" multiple class="sr-only" @change="added = [...$event.target.files].map(f => URL.createObjectURL(f))">
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-6">
        <h2 class="flex items-center gap-3 text-lg font-semibold"><span class="grid h-7 w-7 place-items-center rounded-full bg-brand-600 text-sm text-white">3</span> Location</h2>
        <p class="text-sm text-slate-500">Pin the exact spot so players can find you with “Turfs near me”.</p>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <div class="md:col-span-3"><label class="label">Address *</label><input name="address" value="{{ old('address', $turf->address) }}" class="input" placeholder="House/Road, landmark" required></div>
            <div><label class="label">Area *</label><input name="area" value="{{ old('area', $turf->area) }}" class="input" placeholder="Mirpur 10" required></div>
            <div><label class="label">City *</label><input name="city" value="{{ old('city', $turf->city) }}" class="input" required></div>
            <div class="flex items-end"><button type="button" id="locate" class="btn-light w-full">📍 Use my current location</button></div>
        </div>
        <div id="pick-map" class="mt-4 h-72 rounded-xl ring-1 ring-slate-200"></div>
        <div class="mt-2 grid grid-cols-2 gap-3">
            <input name="latitude" id="lat" value="{{ old('latitude', $turf->latitude) }}" class="input text-xs" placeholder="Latitude" readonly>
            <input name="longitude" id="lng" value="{{ old('longitude', $turf->longitude) }}" class="input text-xs" placeholder="Longitude" readonly>
        </div>
        <p class="mt-1 text-xs text-slate-500">Click on the map to set the pin.</p>
    </div>

    <div class="card p-6">
        <h2 class="flex items-center gap-3 text-lg font-semibold"><span class="grid h-7 w-7 place-items-center rounded-full bg-brand-600 text-sm text-white">4</span> Hours & pricing</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
            <div><label class="label">Opens at *</label><input type="time" name="opens_at" value="{{ old('opens_at', $t($turf->opens_at)) }}" class="input" required></div>
            <div><label class="label">Closes at *</label><input type="time" name="closes_at" value="{{ old('closes_at', $t($turf->closes_at)) }}" class="input" required><p class="mt-1 text-xs text-slate-500">Earlier than opening = closes after midnight.</p></div>
            <div><label class="label">Slot length *</label>
                <select name="slot_minutes" class="input">
                    @foreach ([60 => '1 hour', 90 => '1.5 hours', 120 => '2 hours'] as $v => $l)<option value="{{ $v }}" @selected(old('slot_minutes', $turf->slot_minutes) == $v)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <div><label class="label">Day price per hour (৳) *</label><input type="number" name="price_per_hour" value="{{ old('price_per_hour', $turf->price_per_hour) }}" class="input" required></div>
            <div><label class="label">Night price per hour (৳)</label><input type="number" name="night_price_per_hour" value="{{ old('night_price_per_hour', $turf->night_price_per_hour) }}" class="input" placeholder="optional"></div>
            <div><label class="label">Night price starts at</label><input type="time" name="night_starts_at" value="{{ old('night_starts_at', $t($turf->night_starts_at)) }}" class="input"></div>
        </div>
    </div>

    <div class="card p-6">
        <h2 class="flex items-center gap-3 text-lg font-semibold"><span class="grid h-7 w-7 place-items-center rounded-full bg-brand-600 text-sm text-white">5</span> Receiving advance payments</h2>
        <p class="text-sm text-slate-500">Players send the advance <b>directly to your number</b> and submit the TrxID. You verify it in your app and confirm. No gateway fees.</p>
        <div class="mt-4 grid gap-4 md:grid-cols-4">
            <div><label class="label">Advance % *</label><input type="number" name="advance_percent" min="10" max="100" value="{{ old('advance_percent', $turf->advance_percent) }}" class="input" required></div>
            <div><label class="label text-pink-600">bKash number</label><input name="bkash_number" value="{{ old('bkash_number', $turf->bkash_number) }}" class="input" placeholder="01XXXXXXXXX"></div>
            <div><label class="label text-orange-600">Nagad number</label><input name="nagad_number" value="{{ old('nagad_number', $turf->nagad_number) }}" class="input"></div>
            <div><label class="label text-purple-600">Rocket number</label><input name="rocket_number" value="{{ old('rocket_number', $turf->rocket_number) }}" class="input"></div>
            <div class="md:col-span-4"><label class="label">Payment instruction for players</label><input name="payment_note" value="{{ old('payment_note', $turf->payment_note) }}" class="input" placeholder="e.g. Use Send Money (personal number). Write booking code in reference."></div>
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('panel.turfs.index') }}" class="btn-light">Cancel</a>
        <button class="btn-primary btn-lg px-8">{{ $turf->exists ? 'Save changes' : 'Submit turf' }}</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const latI = document.getElementById('lat'), lngI = document.getElementById('lng');
    const start = latI.value ? [+latI.value, +lngI.value] : [23.7808, 90.4125];
    const map = L.map('pick-map').setView(start, latI.value ? 16 : 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
    let marker = latI.value ? L.marker(start).addTo(map) : null;
    const set = (lat, lng) => {
        latI.value = lat.toFixed(7); lngI.value = lng.toFixed(7);
        marker ? marker.setLatLng([lat, lng]) : (marker = L.marker([lat, lng]).addTo(map));
    };
    map.on('click', e => set(e.latlng.lat, e.latlng.lng));
    document.getElementById('locate').onclick = () => navigator.geolocation?.getCurrentPosition(p => {
        set(p.coords.latitude, p.coords.longitude); map.setView([p.coords.latitude, p.coords.longitude], 17);
    });
});
</script>
@endpush
