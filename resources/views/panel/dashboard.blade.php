@extends('layouts.panel')
@section('title', 'Dashboard')
@section('subtitle', 'Your day at a glance — payments to check and games coming up.')
@section('content')
@php
    $u = auth()->user();
    $hour = now()->hour;
    $greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

    // Setup checklist (owners only) — disappears once everything is done.
    $checklist = $u->isOwner() ? [
        ['Submit verification documents', $u->verification_submitted_at || $u->isVerifiedOwner(), route('panel.verification')],
        ['Add your first turf', $turfs->isNotEmpty(), route('panel.turfs.create')],
        ['Upload a real photo of your turf', $turfs->contains(fn ($t) => $t->hasOwnPhoto()), $turfs->first() ? route('panel.turfs.edit', $turfs->first()) : route('panel.turfs.create')],
        ['Get verified by the admin', $u->isVerifiedOwner(), route('panel.verification')],
        ['Turf is live for players', $turfs->contains(fn ($t) => $t->isLive()), route('panel.turfs.index')],
    ] : [];
    $done = collect($checklist)->where(1, true)->count();
@endphp

{{-- ===== Welcome banner ===== --}}
<div class="relative overflow-hidden rounded-3xl text-white">
    <img src="{{ asset('images/art/banner-owner.svg') }}" alt="" class="absolute inset-0 h-full w-full object-cover">
    <div class="relative flex flex-col gap-5 p-6 md:flex-row md:items-center md:justify-between md:p-8">
        <div>
            <p class="text-sm text-white/75">{{ now()->format('l, d F') }}</p>
            <h2 class="mt-1 text-2xl font-bold md:text-3xl">{{ $greet }}, {{ strtok($u->name, ' ') }}!</h2>
            <p class="mt-1 text-white/80">
                @if ($stats['to_verify']) You have <b>{{ $stats['to_verify'] }}</b> {{ Str::plural('payment', $stats['to_verify']) }} to check.
                @elseif ($stats['today']) <b>{{ $stats['today'] }}</b> {{ Str::plural('game', $stats['today']) }} on your turf today.
                @else Everything is under control. @endif
            </p>
        </div>
        @if ($turfs->isNotEmpty())
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('panel.schedule') }}" class="btn bg-white text-brand-800 hover:bg-brand-50"><x-icon name="plus" class="h-4 w-4"/> Add walk-in</a>
                <a href="{{ route('panel.bookings.index') }}" class="btn bg-white/15 text-white ring-1 ring-white/25 hover:bg-white/25">All bookings</a>
            </div>
        @endif
    </div>
</div>

{{-- ===== Setup checklist ===== --}}
@if ($checklist && $done < count($checklist))
    <div class="card mt-6 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="card-title">Get your turf online</h3>
                <p class="text-sm text-slate-500">{{ $done }} of {{ count($checklist) }} steps done</p>
            </div>
            <div class="h-2 w-48 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-brand-500" style="width: {{ round($done / count($checklist) * 100) }}%"></div></div>
        </div>
        <ol class="mt-5 grid gap-2 md:grid-cols-5">
            @foreach ($checklist as [$label, $ok, $link])
                <li>
                    <a href="{{ $link }}" class="flex h-full items-start gap-3 rounded-2xl p-3 text-sm ring-1 transition {{ $ok ? 'bg-brand-50 text-brand-900 ring-brand-200' : 'ring-slate-200 hover:ring-brand-400' }}">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-bold {{ $ok ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-500' }}">
                            @if ($ok)<x-icon name="check" class="h-4 w-4"/>@else{{ $loop->iteration }}@endif
                        </span>
                        <span class="{{ $ok ? 'line-through decoration-brand-400/60' : 'font-medium' }}">{{ $label }}</span>
                    </a>
                </li>
            @endforeach
        </ol>
    </div>
@endif

@if ($turfs->isEmpty())
    <div class="card mt-6 grid items-center overflow-hidden md:grid-cols-2">
        <img src="{{ asset('images/art/turf-6.svg') }}" alt="" class="h-full w-full object-cover">
        <div class="p-8">
            <p class="text-xl font-semibold">Let's get your turf online</p>
            @if ($u->isOwner())
                <p class="mt-1 text-slate-500">Add your turf's name, location, hours, prices and bKash/Nagad number. It takes about 5 minutes.</p>
                <a href="{{ route('panel.turfs.create') }}" class="btn-primary mt-5"><x-icon name="plus" class="h-4 w-4"/> Add your first turf</a>
            @else
                <p class="mt-1 text-slate-500">You haven't been given access to any turf yet. Ask the owner to add you to a turf from their Staff page.</p>
            @endif
        </div>
    </div>
@else
    {{-- ===== Stats ===== --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['calendar', 'Games today', $stats['today'], 'bg-sky-100 text-sky-700', route('panel.schedule')],
            ['wallet', 'Payments to check', $stats['to_verify'], 'bg-violet-100 text-violet-700', route('panel.bookings.index', ['status' => 'payment_submitted'])],
            ['clock', 'Waiting for payment', $stats['awaiting'], 'bg-amber-100 text-amber-700', route('panel.bookings.index', ['status' => 'pending_payment'])],
            ['chart', 'Collected this month', '৳'.number_format($stats['month_collected']), 'bg-brand-100 text-brand-700', $u->isOwner() ? route('panel.reports') : null],
        ] as [$icon, $label, $value, $tone, $link])
            <a @if ($link) href="{{ $link }}" @endif class="card card-hover flex items-center gap-4 p-5">
                <span class="icon-tile {{ $tone }}"><x-icon :name="$icon"/></span>
                <div><p class="text-sm text-slate-500">{{ $label }}</p><p class="text-2xl font-bold text-slate-900">{{ $value }}</p></div>
            </a>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        {{-- Payments to verify --}}
        <div class="card">
            <div class="card-head">
                <div><h3 class="card-title">Check these payments</h3><p class="text-xs text-slate-500">Find each TrxID in your bKash/Nagad app, then tap “Payment received”.</p></div>
                @if ($stats['to_verify'])<span class="badge bg-violet-100 text-violet-800">{{ $stats['to_verify'] }}</span>@endif
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($toVerify as $b)
                    <div class="space-y-3 p-5">
                        <div class="flex justify-between gap-3">
                            <div>@include('panel.bookings.row-info')</div>
                            <div class="text-right text-sm"><p class="font-semibold">{{ $b->starts_at->format('d M, g:i A') }}</p><p class="text-xs text-slate-500">{{ $b->turf->name }}</p></div>
                        </div>
                        @include('panel.bookings.actions')
                    </div>
                @empty
                    <div class="p-10 text-center">
                        <span class="icon-tile mx-auto bg-brand-50 text-brand-600"><x-icon name="check"/></span>
                        <p class="mt-3 text-sm text-slate-500">Nothing to check right now.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Today's games --}}
        <div class="card">
            <div class="card-head">
                <div><h3 class="card-title">Today's games</h3><p class="text-xs text-slate-500">Including tonight's late games.</p></div>
                <a href="{{ route('panel.schedule') }}" class="btn-ghost btn-sm">Full schedule <x-icon name="arrow-right" class="h-4 w-4"/></a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($todayList as $b)
                    <div class="flex items-center gap-4 px-5 py-3.5">
                        <div class="w-16 shrink-0 rounded-xl bg-slate-50 py-1.5 text-center ring-1 ring-slate-200">
                            <p class="text-sm font-bold">{{ $b->starts_at->format('g:i') }}</p><p class="text-[10px] uppercase text-slate-500">{{ $b->starts_at->format('A') }}</p>
                        </div>
                        <div class="min-w-0 flex-1 text-sm">
                            <p class="truncate font-semibold">{{ $b->customer_name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $b->turf->name }} · <a href="tel:{{ $b->customer_phone }}" class="hover:text-brand-700">{{ $b->customer_phone }}</a></p>
                        </div>
                        @include('partials.status', ['booking' => $b])
                    </div>
                @empty
                    <div class="p-10 text-center">
                        <span class="icon-tile mx-auto bg-slate-100 text-slate-500"><x-icon name="calendar"/></span>
                        <p class="mt-3 text-sm text-slate-500">No games today yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endif
@endsection
