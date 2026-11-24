@extends('layouts.panel')
@section('title', 'Dashboard')
@section('subtitle', 'Everything that needs your decision, plus how the platform is doing.')
@section('content')
@php($todo = $stats['pending_owners'] + $stats['pending_turfs'])

{{-- ===== Banner ===== --}}
<div class="relative overflow-hidden rounded-3xl text-white">
    <img src="{{ asset('images/art/banner-admin.svg') }}" alt="" class="absolute inset-0 h-full w-full object-cover">
    <div class="relative p-6 md:p-8">
        <p class="text-sm text-white/70">{{ now()->format('l, d F') }}</p>
        <h2 class="mt-1 text-2xl font-bold md:text-3xl">Hello, {{ strtok(auth()->user()->name, ' ') }}</h2>
        <p class="mt-1 text-white/80">
            @if ($todo) <b>{{ $todo }}</b> {{ Str::plural('item', $todo) }} {{ $todo === 1 ? 'needs' : 'need' }} your review.
            @else Nothing waiting for you. The platform is running smoothly. @endif
        </p>
    </div>
</div>

{{-- ===== Needs attention ===== --}}
<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <div class="card">
        <div class="card-head">
            <div class="flex items-center gap-3">
                <span class="icon-tile !h-10 !w-10 bg-amber-100 text-amber-700"><x-icon name="shield"/></span>
                <div><h3 class="card-title">Owners to verify</h3><p class="text-xs text-slate-500">Check their NID and business documents.</p></div>
            </div>
            <a href="{{ route('admin.owners') }}" class="btn-ghost btn-sm">Open <x-icon name="arrow-right" class="h-4 w-4"/></a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($pendingOwners as $o)
                <div class="flex items-center justify-between gap-3 px-5 py-3.5">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-100 text-sm font-bold text-slate-600">{{ strtoupper(mb_substr($o->name, 0, 1)) }}</span>
                        <div class="min-w-0"><p class="truncate text-sm font-semibold">{{ $o->name }} <span class="font-normal text-slate-500">· {{ $o->business_name }}</span></p>
                            <p class="text-xs text-slate-500">{{ $o->phone }} · sent {{ $o->verification_submitted_at?->diffForHumans() }}</p></div>
                    </div>
                    <a href="{{ route('admin.owners', ['q' => $o->phone]) }}" class="btn-primary btn-sm shrink-0">Review</a>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-slate-500"><span class="icon-tile mx-auto mb-2 bg-brand-50 text-brand-600"><x-icon name="check"/></span>No owners waiting.</div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div class="flex items-center gap-3">
                <span class="icon-tile !h-10 !w-10 bg-sky-100 text-sky-700"><x-icon name="pitch"/></span>
                <div><h3 class="card-title">Turfs to approve</h3><p class="text-xs text-slate-500">Open the page to preview, then approve.</p></div>
            </div>
            <a href="{{ route('admin.turfs', ['status' => 'pending']) }}" class="btn-ghost btn-sm">Open <x-icon name="arrow-right" class="h-4 w-4"/></a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($pendingTurfs as $t)
                <div class="flex items-center justify-between gap-3 px-5 py-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <img src="{{ $t->coverUrl() }}" alt="" class="h-10 w-14 shrink-0 rounded-lg object-cover">
                        <div class="min-w-0">
                            <a href="{{ route('turfs.show', $t) }}" target="_blank" class="flex items-center gap-1 truncate text-sm font-semibold hover:text-brand-700">{{ $t->name }} <x-icon name="external" class="h-3.5 w-3.5"/></a>
                            <p class="truncate text-xs text-slate-500">{{ $t->area }} · {{ $t->owner->name }}
                                @unless ($t->owner->isVerifiedOwner())<span class="text-amber-700">· owner not verified</span>@endunless</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.turfs.status', $t) }}">@csrf<input type="hidden" name="status" value="approved"><button class="btn-primary btn-sm">Approve</button></form>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-slate-500"><span class="icon-tile mx-auto mb-2 bg-brand-50 text-brand-600"><x-icon name="check"/></span>No turfs waiting.</div>
            @endforelse
        </div>
    </div>
</div>

{{-- ===== Stats ===== --}}
<h3 class="mt-10 text-sm font-semibold uppercase tracking-wider text-slate-500">Platform</h3>
<div class="mt-3 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([
        ['pitch', 'Live turfs', $stats['turfs'], 'bg-brand-100 text-brand-700', route('admin.turfs', ['status' => 'approved'])],
        ['users', 'Turf owners', $stats['owners'], 'bg-sky-100 text-sky-700', route('admin.owners', ['status' => 'all'])],
        ['user', 'Players', $stats['customers'], 'bg-violet-100 text-violet-700', route('admin.users', ['role' => 'customer'])],
        ['calendar', 'Games this month', $stats['bookings_month'], 'bg-amber-100 text-amber-700', route('admin.bookings')],
        ['wallet', 'Booked value this month', '৳'.number_format($stats['gmv_month']), 'bg-emerald-100 text-emerald-700', route('admin.bookings')],
        ['x', 'Auto-cancelled (unpaid)', $stats['auto_cancelled_month'], 'bg-rose-100 text-rose-700', route('admin.bookings', ['status' => 'cancelled'])],
        ['clock', 'Payment deadline', \App\Models\Setting::get('payment_deadline_hours').' hours', 'bg-slate-100 text-slate-700', route('admin.settings')],
        ['shield', 'Owners waiting', $stats['pending_owners'], 'bg-amber-100 text-amber-700', route('admin.owners')],
    ] as [$icon, $l, $v, $tone, $link])
        <a href="{{ $link }}" class="card card-hover flex items-center gap-4 p-5">
            <span class="icon-tile {{ $tone }}"><x-icon :name="$icon"/></span>
            <div class="min-w-0"><p class="truncate text-sm text-slate-500">{{ $l }}</p><p class="text-2xl font-bold text-slate-900">{{ $v }}</p></div>
        </a>
    @endforeach
</div>

<div class="card mt-6">
    <div class="card-head"><h3 class="card-title">Top turfs this month</h3></div>
    <div class="grid gap-px bg-slate-100 sm:grid-cols-2 lg:grid-cols-5">
        @forelse ($topTurfs as $t)
            <a href="{{ route('turfs.show', $t) }}" target="_blank" class="group bg-white p-4">
                <div class="relative overflow-hidden rounded-xl">
                    <img src="{{ $t->coverUrl() }}" alt="" class="aspect-[16/10] w-full object-cover transition group-hover:scale-105">
                    <span class="badge absolute left-2 top-2 bg-white/95 text-slate-800">#{{ $loop->iteration }}</span>
                </div>
                <p class="mt-3 truncate text-sm font-semibold">{{ $t->name }}</p>
                <p class="text-xs text-slate-500">{{ $t->bookings_count }} {{ Str::plural('game', $t->bookings_count) }}</p>
            </a>
        @empty
            <p class="bg-white p-6 text-sm text-slate-500">No games yet this month.</p>
        @endforelse
    </div>
</div>
@endsection
