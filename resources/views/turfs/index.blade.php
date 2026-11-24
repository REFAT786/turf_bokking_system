@extends('layouts.app')
@section('title', 'Find a football turf')
@include('partials.leaflet')

@section('content')
<div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
    <div>
        <p class="eyebrow">{{ $lat ? 'Near you' : 'All turfs' }}</p>
        <h1 class="mt-1 text-3xl font-bold tracking-tight">{{ $lat ? 'Turfs near you' : (request('q') ? 'Results for “'.request('q').'”' : 'Find a turf') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $turfs->total() }} turf{{ $turfs->total() === 1 ? '' : 's' }} available</p>
    </div>
    @include('partials.nearby-button')
</div>

<form class="card mt-6 grid gap-3 p-3 md:grid-cols-12">
    @if ($lat)<input type="hidden" name="lat" value="{{ $lat }}"><input type="hidden" name="lng" value="{{ $lng }}">@endif
    <div class="relative md:col-span-5">
        <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"/>
        <input name="q" value="{{ request('q') }}" class="input !pl-10" placeholder="Area, turf name or address">
    </div>
    <select name="city" class="input md:col-span-2">
        <option value="">All cities</option>
        @foreach ($cities as $c)<option @selected(request('city') === $c)>{{ $c }}</option>@endforeach
    </select>
    <select name="max_price" class="input md:col-span-2">
        <option value="">Any price</option>
        @foreach ([1500, 2000, 2500, 3000] as $p)<option value="{{ $p }}" @selected(request('max_price') == $p)>Up to ৳{{ number_format($p) }}/hr</option>@endforeach
    </select>
    @if ($lat)
        <select name="radius" class="input md:col-span-2">
            @foreach ([5, 10, 15, 25, 50] as $r)<option value="{{ $r }}" @selected(request('radius', 15) == $r)>Within {{ $r }} km</option>@endforeach
            <option value="0" @selected(request('radius') === '0')>Any distance</option>
        </select>
    @else
        <select name="sort" class="input md:col-span-2">
            <option value="">Newest first</option>
            <option value="price" @selected(request('sort') === 'price')>Lowest price</option>
            <option value="rating" @selected(request('sort') === 'rating')>Top rated</option>
        </select>
    @endif
    <div class="flex gap-2 md:col-span-1">
        <button class="btn-primary flex-1" aria-label="Search"><x-icon name="search" class="h-4 w-4"/></button>
    </div>
</form>
@if (request()->hasAny(['q', 'city', 'max_price', 'sort', 'lat']))
    <a href="{{ route('turfs.index') }}" class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800"><x-icon name="x" class="h-4 w-4"/> Clear filters</a>
@endif

@if ($turfs->isEmpty())
    <div class="card mt-8 grid items-center gap-6 overflow-hidden md:grid-cols-2">
        <img src="{{ asset('images/art/turf-5.svg') }}" alt="" class="h-full w-full object-cover">
        <div class="p-8">
            <p class="text-xl font-semibold">No turfs match your search.</p>
            <p class="mt-2 text-slate-500">Try another area{{ $lat ? ', a bigger distance' : '' }} or clear the filters.</p>
            <a href="{{ route('turfs.index') }}" class="btn-primary mt-5">See all turfs</a>
        </div>
    </div>
@else
    <div class="mt-8 grid gap-6 lg:grid-cols-5">
        <div class="grid content-start gap-6 sm:grid-cols-2 lg:col-span-3">
            @foreach ($turfs as $turf) @include('partials.turf-card') @endforeach
        </div>
        <div class="hidden lg:col-span-2 lg:block">
            <div id="map" class="card sticky top-24 h-[calc(100vh-8rem)] overflow-hidden"></div>
        </div>
    </div>
    <div class="mt-8">{{ $turfs->links() }}</div>
@endif
@endsection

@push('scripts')
@php
    $mapPoints = $turfs->getCollection()->filter(fn ($t) => $t->latitude)->map(fn ($t) => [
        'name' => $t->name, 'lat' => (float) $t->latitude, 'lng' => (float) $t->longitude,
        'url' => route('turfs.show', $t), 'price' => number_format($t->minPrice()), 'area' => $t->area, 'img' => $t->coverUrl(),
    ])->values();
@endphp
<script>
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('map');
    if (!el || !window.L) return;
    const turfs = @json($mapPoints);
    const map = L.map(el, { scrollWheelZoom: false }).setView([23.7808, 90.4125], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
    const pin = L.divIcon({ className: '', iconSize: [34, 34], iconAnchor: [17, 34],
        html: '<div style="width:34px;height:34px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);background:#059669;border:3px solid #fff;box-shadow:0 4px 10px rgba(0,0,0,.3)"></div>' });
    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const pts = [];
    turfs.forEach(t => {
        L.marker([t.lat, t.lng], { icon: pin }).addTo(map).bindPopup(
            `<div style="width:190px"><img src="${esc(t.img)}" style="width:100%;height:90px;object-fit:cover;border-radius:10px"><b style="display:block;margin-top:6px">${esc(t.name)}</b><span style="color:#64748b">${esc(t.area)} · from ৳${esc(t.price)}/hr</span><br><a href="${esc(t.url)}" style="color:#059669;font-weight:600">Book now →</a></div>`);
        pts.push([t.lat, t.lng]);
    });
    @if ($lat)
        L.circleMarker([{{ (float) $lat }}, {{ (float) $lng }}], { radius: 9, color: '#2563eb', fillOpacity: .9 }).addTo(map).bindPopup('You are here');
        pts.push([{{ (float) $lat }}, {{ (float) $lng }}]);
    @endif
    if (pts.length) map.fitBounds(pts, { padding: [40, 40], maxZoom: 14 });
});
</script>
@endpush
