@extends('layouts.app')
@section('title', $turf->name)
@include('partials.leaflet')

@section('content')
@php
    $user = auth()->user();
    $deadline = \App\Models\Setting::get('payment_deadline_hours');
    $photos = $turf->photoUrls();
    $fmt = fn ($t) => \Carbon\Carbon::parse($t)->format('g:i A');
@endphp

@if (! $turf->isLive())
    <div class="mb-5 flex items-start gap-3 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
        <x-icon name="eye" class="mt-0.5 h-5 w-5 shrink-0"/>
        <p><b>Preview only</b> — players can't see this page yet. {{ $turf->publicState()['hint'] }}</p>
    </div>
@endif

{{-- ===== Gallery ===== --}}
<div x-data="{ open: false, i: 0, photos: @js($photos) }" @keydown.escape.window="open = false"
     @keydown.arrow-right.window="if (open) i = (i + 1) % photos.length" @keydown.arrow-left.window="if (open) i = (i - 1 + photos.length) % photos.length">
    <div class="grid h-64 gap-2 overflow-hidden rounded-3xl sm:h-80 md:h-[26rem] {{ count($photos) > 1 ? 'md:grid-cols-4 md:grid-rows-2' : '' }}">
        @foreach (array_slice($photos, 0, 5) as $k => $src)
            <button type="button" @click="open = true; i = {{ $k }}"
                    class="group relative overflow-hidden bg-slate-200 {{ $k === 0 ? (count($photos) > 1 ? 'md:col-span-2 md:row-span-2' : '') : 'hidden md:block' }} {{ count($photos) === 2 && $k === 1 ? 'md:col-span-2 md:row-span-2' : '' }}">
                <img src="{{ $src }}" alt="{{ $turf->name }} photo {{ $k + 1 }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                @if ($k === 4 && count($photos) > 5)
                    <span class="absolute inset-0 grid place-items-center bg-black/50 text-lg font-semibold text-white">+{{ count($photos) - 5 }} more</span>
                @endif
            </button>
        @endforeach
    </div>
    @if (count($photos) > 1)
        <button type="button" @click="open = true; i = 0" class="btn-light btn-sm relative -mt-12 ml-3 md:hidden"><x-icon name="image" class="h-4 w-4"/> {{ count($photos) }} photos</button>
    @endif

    <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4" @click.self="open = false">
        <button @click="open = false" class="absolute right-4 top-4 grid h-10 w-10 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="Close"><x-icon name="x"/></button>
        <button x-show="photos.length > 1" @click="i = (i - 1 + photos.length) % photos.length" class="absolute left-4 grid h-11 w-11 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="Previous"><x-icon name="arrow-left"/></button>
        <img :src="photos[i]" alt="" class="max-h-[85vh] max-w-full rounded-2xl object-contain">
        <button x-show="photos.length > 1" @click="i = (i + 1) % photos.length" class="absolute right-4 grid h-11 w-11 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="Next"><x-icon name="arrow-right"/></button>
        <p class="absolute bottom-4 text-sm text-white/70" x-text="(i + 1) + ' / ' + photos.length"></p>
    </div>
</div>

<div class="mt-8 grid gap-8 lg:grid-cols-3">
    <div class="min-w-0 space-y-8 lg:col-span-2">
        {{-- ===== Title & facts ===== --}}
        <div>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">{{ $turf->name }}</h1>
                    <p class="mt-1 flex items-center gap-1.5 text-slate-500"><x-icon name="pin" class="h-4 w-4"/> {{ $turf->address }}, {{ $turf->area }}, {{ $turf->city }}</p>
                </div>
                @if ($rating)
                    <div class="rounded-2xl bg-amber-50 px-4 py-2 text-center ring-1 ring-amber-200">
                        <p class="text-xl font-bold text-amber-600">★ {{ number_format($rating, 1) }}</p>
                        <p class="text-xs text-amber-800/70">{{ $reviews->count() }} {{ Str::plural('review', $reviews->count()) }}</p>
                    </div>
                @endif
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach (array_filter([
                    ['sun', 'Day price', '৳'.number_format($turf->price_per_hour).'/hr', 'bg-amber-100 text-amber-700'],
                    $turf->night_price_per_hour ? ['moon', 'Night from '.$fmt($turf->night_starts_at), '৳'.number_format($turf->night_price_per_hour).'/hr', 'bg-indigo-100 text-indigo-700'] : null,
                    ['clock', 'Open', \Carbon\Carbon::parse($turf->opens_at)->format('g A').' – '.\Carbon\Carbon::parse($turf->closes_at)->format('g A'), 'bg-sky-100 text-sky-700'],
                    ['pitch', 'Size', $turf->size ?: '—', 'bg-brand-100 text-brand-700'],
                ]) as [$icon, $label, $value, $tone])
                    <div class="card flex items-center gap-3 p-3">
                        <span class="icon-tile !h-10 !w-10 {{ $tone }}"><x-icon :name="$icon" class="h-5 w-5"/></span>
                        <div class="min-w-0"><p class="truncate text-xs text-slate-500">{{ $label }}</p><p class="truncate font-semibold">{{ $value }}</p></div>
                    </div>
                @endforeach
            </div>

            @if ($turf->description)<p class="mt-6 whitespace-pre-line leading-relaxed text-slate-700">{{ $turf->description }}</p>@endif
            @if ($turf->facilities)
                <div class="mt-5 flex flex-wrap gap-2">
                    @foreach ($turf->facilities as $f)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-sm ring-1 ring-slate-200"><x-icon name="check" class="h-4 w-4 text-brand-600"/> {{ \App\Models\Turf::FACILITIES[$f] ?? $f }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ===== Slot picker ===== --}}
        <div class="card overflow-hidden" id="book" x-data="slotPicker()">
            <div class="border-b border-slate-100 bg-gradient-to-r from-brand-50 to-white p-5">
                <h2 class="text-xl font-bold">Pick a date & time</h2>
                <p class="text-sm text-slate-500">Tap a free hour. Tap the next one to book a longer game (up to 6 hours).</p>
            </div>
            <div class="p-5">
                {{-- Date picker: scrollable strip with arrows + a calendar to jump to any bookable day --}}
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-slate-700">{{ $date->isToday() ? 'Today' : $date->format('l') }}, {{ $date->format('d F Y') }}</p>
                    <form action="{{ route('turfs.show', $turf) }}#book" class="flex items-center gap-2" x-data>
                        <label class="btn-light btn-sm relative cursor-pointer"><x-icon name="calendar" class="h-4 w-4"/> Pick a date
                            <input type="date" name="date" value="{{ $date->toDateString() }}" min="{{ $days->first()->toDateString() }}" max="{{ $days->last()->toDateString() }}"
                                   class="absolute inset-0 cursor-pointer opacity-0" @change="$el.form.submit()" onclick="this.showPicker && this.showPicker()">
                        </label>
                    </form>
                </div>
                <div class="relative mt-3" x-data="{ el: null, start: true, end: false,
                        upd() { this.start = this.el.scrollLeft < 8; this.end = this.el.scrollLeft + this.el.clientWidth > this.el.scrollWidth - 8 },
                        go(d) { this.el.scrollBy({ left: d * this.el.clientWidth * .8, behavior: 'smooth' }) } }"
                     x-init="el = $refs.strip; $nextTick(() => { $refs.strip.querySelector('[data-active]')?.scrollIntoView({ inline: 'center', block: 'nearest' }); upd() })">
                    <div x-ref="strip" @scroll.debounce.50ms="upd()" class="no-scrollbar flex gap-2 overflow-x-auto scroll-smooth px-1 py-1">
                        @foreach ($days as $d)
                            <a href="{{ route('turfs.show', [$turf, 'date' => $d->toDateString()]) }}#book" @if ($d->isSameDay($date)) data-active @endif
                               class="w-16 shrink-0 rounded-2xl py-2.5 text-center ring-1 transition {{ $d->isSameDay($date) ? 'bg-brand-600 text-white ring-brand-600 shadow-md shadow-brand-600/30' : 'bg-white ring-slate-200 hover:ring-brand-400' }}">
                                <span class="block text-[11px] font-medium uppercase {{ $d->isSameDay($date) ? 'text-white/80' : 'text-slate-500' }}">{{ $d->isToday() ? 'Today' : ($d->isTomorrow() ? 'Tmrw' : $d->format('D')) }}</span>
                                <span class="block text-lg font-bold leading-tight">{{ $d->format('d') }}</span>
                                <span class="block text-[11px] {{ $d->isSameDay($date) ? 'text-white/80' : 'text-slate-400' }}">{{ $d->format('M') }}</span>
                            </a>
                        @endforeach
                    </div>
                    {{-- edge fades + arrows (only when there is more to scroll) --}}
                    <div x-show="!start" x-cloak class="pointer-events-none absolute inset-y-0 left-0 w-16 bg-gradient-to-r from-white to-transparent"></div>
                    <div x-show="!end" class="pointer-events-none absolute inset-y-0 right-0 w-16 bg-gradient-to-l from-white to-transparent"></div>
                    <button type="button" x-show="!start" x-cloak @click="go(-1)" class="absolute left-0 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-white text-slate-700 shadow-md ring-1 ring-slate-200 hover:text-brand-700" aria-label="Earlier dates"><x-icon name="arrow-left" class="h-4 w-4"/></button>
                    <button type="button" x-show="!end" @click="go(1)" class="absolute right-0 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-white text-slate-700 shadow-md ring-1 ring-slate-200 hover:text-brand-700" aria-label="Later dates"><x-icon name="arrow-right" class="h-4 w-4"/></button>
                </div>
                <p class="mt-2 text-xs text-slate-500">You can book from today up to <b>{{ $days->last()->format('d M') }}</b> ({{ $days->count() - 1 }} days ahead).</p>

                <div class="mt-4 flex flex-wrap gap-4 text-xs text-slate-500">
                    <span class="flex items-center gap-1.5"><i class="h-3 w-3 rounded bg-white ring-2 ring-brand-400"></i> Free</span>
                    <span class="flex items-center gap-1.5"><i class="h-3 w-3 rounded bg-brand-600"></i> Your pick</span>
                    <span class="flex items-center gap-1.5"><i class="h-3 w-3 rounded bg-amber-200"></i> On hold</span>
                    <span class="flex items-center gap-1.5"><i class="h-3 w-3 rounded bg-slate-200"></i> Booked</span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                    @foreach ($slots as $i => $s)
                        @php($free = $s['status'] === 'available')
                        <button type="button"
                            @if ($free) @click="toggle({{ $i }}, '{{ $s['start']->toDateTimeString() }}', {{ $s['price'] }}, '{{ $s['start']->format('g:i A') }}', '{{ $s['end']->format('g:i A') }}')" @else disabled @endif
                            :class="isSel({{ $i }}) && '!bg-brand-600 !text-white !ring-brand-600 shadow-md shadow-brand-600/30'"
                            class="rounded-2xl px-3 py-3 text-left ring-1 transition
                                {{ $free ? 'bg-white ring-brand-300 hover:ring-2 hover:ring-brand-500' : ($s['status'] === 'held' ? 'cursor-not-allowed bg-amber-50 text-amber-700 ring-amber-200' : 'cursor-not-allowed bg-slate-100 text-slate-400 ring-slate-200') }}">
                            <span class="flex items-center gap-1 font-semibold">{{ $s['start']->format('g:i A') }}@if ($s['night'])<x-icon name="moon" class="h-3.5 w-3.5 opacity-70"/>@endif</span>
                            <span class="block text-xs opacity-80">
                                @switch($s['status'])
                                    @case('available') ৳{{ number_format($s['price']) }} @break
                                    @case('held') On hold @break
                                    @case('past') Passed @break
                                    @default Booked
                                @endswitch
                                @if (! $s['start']->isSameDay($date)) · next day @endif
                            </span>
                        </button>
                    @endforeach
                </div>
                @if (empty($slots))<p class="text-sm text-slate-500">No slots on this day.</p>@endif

                {{-- Booking summary --}}
                <div x-show="sel.length" x-cloak x-transition class="mt-6 rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs text-slate-500">Your game</p>
                            <p class="font-semibold"><span x-text="sel[0]?.label"></span> – <span x-text="sel[sel.length - 1]?.end"></span> · <span x-text="(sel.length * {{ $turf->slot_minutes }} / 60) + ' hr'"></span></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-slate-500">Total <b class="text-base text-slate-900">৳<span x-text="total.toLocaleString()"></span></b></p>
                            <p class="text-sm">Advance now <b class="text-lg text-brand-700">৳<span x-text="Math.ceil(total * {{ $turf->advance_percent }} / 100).toLocaleString()"></span></b></p>
                        </div>
                    </div>

                    @auth
                        @if ($user->isCustomer())
                            <form method="POST" action="{{ route('turfs.book', $turf) }}" class="mt-5 grid gap-3 sm:grid-cols-2">
                                @csrf
                                <template x-for="s in sel"><input type="hidden" name="slots[]" :value="s.start"></template>
                                <div><label class="label">Your name</label><input name="name" class="input" value="{{ old('name', $user->name) }}" required></div>
                                <div><label class="label">Mobile</label><input name="phone" class="input" value="{{ old('phone', $user->phone) }}" required></div>
                                <div class="sm:col-span-2"><label class="label">Note for the turf <span class="font-normal text-slate-400">(optional)</span></label><input name="notes" class="input" placeholder="e.g. 7-a-side, need bibs"></div>
                                <div class="sm:col-span-2">
                                    <button class="btn-primary btn-lg w-full">Reserve now</button>
                                    <p class="mt-2 text-center text-xs text-slate-500">Pay the advance within {{ $deadline }} hours to confirm — otherwise the booking is cancelled automatically.</p>
                                </div>
                            </form>
                        @else
                            <p class="mt-4 rounded-xl bg-white p-3 text-sm text-slate-500 ring-1 ring-slate-200">You're logged in as {{ str_replace('_', ' ', $user->role) }}. Use a player account to book online.</p>
                        @endif
                    @else
                        <div class="mt-5 grid gap-2 sm:grid-cols-2">
                            <a href="{{ route('login') }}" class="btn-primary btn-lg">Log in to book</a>
                            <a href="{{ route('register') }}" class="btn-light btn-lg">Create free account</a>
                        </div>
                        <p class="mt-2 text-center text-xs text-slate-500">New here? Creating an account takes 20 seconds.</p>
                    @endauth
                </div>
            </div>
        </div>

        {{-- ===== Reviews ===== --}}
        @if ($reviews->isNotEmpty())
            <div>
                <h2 class="text-xl font-bold">What players say</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ($reviews as $r)
                        <div class="card p-5">
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 w-9 place-items-center rounded-full bg-brand-100 font-bold text-brand-800">{{ strtoupper(mb_substr($r->user->name, 0, 1)) }}</span>
                                <div><p class="text-sm font-semibold">{{ $r->user->name }}</p><p class="text-xs text-slate-400">{{ $r->created_at->diffForHumans() }}</p></div>
                                <span class="ml-auto text-amber-500">{{ str_repeat('★', $r->rating) }}<span class="text-slate-200">{{ str_repeat('★', 5 - $r->rating) }}</span></span>
                            </div>
                            @if ($r->comment)<p class="mt-3 text-sm text-slate-600">{{ $r->comment }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- ===== Sidebar ===== --}}
    <aside class="space-y-5 lg:sticky lg:top-24 lg:self-start">
        <div class="card p-5">
            <p class="text-sm text-slate-500">Starting from</p>
            <p class="text-3xl font-bold">৳{{ number_format($turf->minPrice()) }}<span class="text-base font-normal text-slate-500">/hr</span></p>
            <a href="#book" class="btn-primary btn-lg mt-4 w-full">Check free slots</a>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <a href="tel:{{ $turf->phone }}" class="btn-light"><x-icon name="phone" class="h-4 w-4"/> Call</a>
                <a href="{{ $turf->whatsappLink('Hi, I found '.$turf->name.' on '.site('brand.name_1').site('brand.name_2').'.') }}" target="_blank" rel="noopener" class="btn bg-[#25D366] text-white hover:bg-[#1fb857]"><x-icon name="whatsapp" class="h-4 w-4"/> WhatsApp</a>
            </div>
        </div>
        @if ($turf->latitude)
            <div class="card overflow-hidden">
                <div id="turf-map" class="h-56"></div>
                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $turf->latitude }},{{ $turf->longitude }}" target="_blank" rel="noopener" class="flex items-center justify-center gap-1.5 p-3 text-sm font-semibold text-brand-700 hover:bg-slate-50"><x-icon name="pin" class="h-4 w-4"/> Get directions</a>
            </div>
        @endif
        <div class="card p-5 text-sm">
            <h3 class="font-semibold text-slate-900">Booking rules</h3>
            <ul class="mt-3 space-y-2.5 text-slate-600">
                <li class="flex gap-2"><x-icon name="wallet" class="h-4 w-4 shrink-0 text-brand-600"/> {{ $turf->advance_percent }}% advance by {{ implode(' / ', array_map('ucfirst', array_keys($turf->paymentAccounts()))) }}</li>
                <li class="flex gap-2"><x-icon name="clock" class="h-4 w-4 shrink-0 text-brand-600"/> Unpaid bookings cancel after {{ $deadline }} hours</li>
                <li class="flex gap-2"><x-icon name="x" class="h-4 w-4 shrink-0 text-brand-600"/> Free cancellation up to {{ \App\Models\Setting::get('min_hours_before_cancel') }} hrs before</li>
                <li class="flex gap-2"><x-icon name="check" class="h-4 w-4 shrink-0 text-brand-600"/> Pay the rest at the turf</li>
            </ul>
        </div>
    </aside>
</div>

{{-- Mobile sticky CTA --}}
<div class="h-16 lg:hidden"></div>
<div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 p-3 backdrop-blur lg:hidden">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3">
        <p class="text-sm">from <b class="text-lg">৳{{ number_format($turf->minPrice()) }}</b>/hr</p>
        <a href="#book" class="btn-primary">Check free slots</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
function slotPicker() {
    return {
        sel: [], total: 0,
        isSel(i) { return this.sel.some(s => s.i === i); },
        toggle(i, start, price, label, end) {
            const idx = this.sel.map(s => s.i);
            if (this.isSel(i)) {
                // deselect: keep it contiguous by trimming from the clicked end
                this.sel = (i === Math.min(...idx) || i === Math.max(...idx)) ? this.sel.filter(s => s.i !== i) : this.sel.filter(s => s.i < i);
            } else {
                const adjacent = !idx.length || i === Math.min(...idx) - 1 || i === Math.max(...idx) + 1;
                if (!adjacent) this.sel = [];
                if (this.sel.length >= 6) return;
                this.sel.push({ i, start, price, label, end });
                this.sel.sort((a, b) => a.i - b.i);
            }
            this.total = this.sel.reduce((t, s) => t + s.price, 0);
        },
    };
}
@if ($turf->latitude)
document.addEventListener('DOMContentLoaded', () => {
    if (!window.L) return;
    const m = L.map('turf-map', { scrollWheelZoom: false, zoomControl: false }).setView([{{ (float) $turf->latitude }}, {{ (float) $turf->longitude }}], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(m);
    L.marker([{{ (float) $turf->latitude }}, {{ (float) $turf->longitude }}]).addTo(m);
});
@endif
</script>
@endpush
