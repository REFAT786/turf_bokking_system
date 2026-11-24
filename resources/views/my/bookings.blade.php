@extends('layouts.app')
@section('title', 'My bookings')
@section('content')
@php
    $upcoming = $bookings->getCollection()->filter(fn ($b) => $b->ends_at->isFuture() && ! in_array($b->status, ['cancelled', 'completed', 'no_show']));
    $past = $bookings->getCollection()->diff($upcoming);
    $needsPay = $upcoming->where('status', 'pending_payment');
@endphp

<div class="relative overflow-hidden rounded-[2rem] text-white">
    <img src="{{ asset('images/art/banner-player.svg') }}" alt="" class="absolute inset-0 h-full w-full object-cover">
    <div class="relative flex flex-col gap-4 p-7 sm:flex-row sm:items-center sm:justify-between md:p-10">
        <div>
            <p class="text-sm text-white/75">Hi {{ strtok(auth()->user()->name, ' ') }} 👋</p>
            <h1 class="mt-1 text-3xl font-bold">My bookings</h1>
            <p class="mt-1 text-white/80">{{ $upcoming->count() }} upcoming {{ Str::plural('game', $upcoming->count()) }}</p>
        </div>
        <a href="{{ route('turfs.index') }}" class="btn bg-white px-5 py-3 text-slate-900 hover:bg-slate-100"><x-icon name="plus" class="h-4 w-4"/> Book a turf</a>
    </div>
</div>

@if ($needsPay->isNotEmpty())
    <div class="mt-6 flex items-start gap-3 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
        <x-icon name="clock" class="mt-0.5 h-5 w-5 shrink-0"/>
        <p><b>{{ $needsPay->count() }} {{ Str::plural('booking', $needsPay->count()) }} waiting for your advance payment.</b> Open it and send the advance before the deadline, or it is cancelled automatically.</p>
    </div>
@endif

@if ($bookings->isEmpty())
    <div class="card mt-8 grid items-center gap-6 overflow-hidden md:grid-cols-2">
        <img src="{{ asset('images/art/turf-3.svg') }}" alt="" class="h-full w-full object-cover">
        <div class="p-8">
            <p class="text-xl font-semibold">No bookings yet</p>
            <p class="mt-1 text-slate-500">Find a turf near you and book your first game.</p>
            <a href="{{ route('turfs.index') }}" class="btn-primary mt-5">Find a turf</a>
        </div>
    </div>
@else
    @foreach ([['Upcoming', $upcoming], ['Past & cancelled', $past]] as [$title, $list])
        @continue($list->isEmpty())
        <h2 class="mt-10 text-lg font-bold">{{ $title }}</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            @foreach ($list as $b)
                <a href="{{ route('my.bookings.show', $b) }}" class="card card-hover flex gap-4 overflow-hidden p-3 {{ $title !== 'Upcoming' ? 'opacity-80' : '' }}">
                    <div class="relative w-28 shrink-0 overflow-hidden rounded-xl sm:w-32">
                        <img src="{{ $b->turf->coverUrl() }}" class="h-full w-full object-cover" alt="">
                        <div class="absolute inset-x-0 bottom-0 bg-black/55 py-1 text-center text-white">
                            <p class="text-[11px] uppercase">{{ $b->starts_at->format('D') }}</p>
                            <p class="text-lg font-bold leading-none">{{ $b->starts_at->format('d M') }}</p>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1 py-1">
                        <div class="flex items-start justify-between gap-2">
                            <p class="truncate font-semibold">{{ $b->turf->name }}</p>
                            @include('partials.status', ['booking' => $b])
                        </div>
                        <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-600"><x-icon name="clock" class="h-4 w-4"/> {{ $b->starts_at->format('g:i A') }} – {{ $b->ends_at->format('g:i A') }}</p>
                        <p class="mt-0.5 text-sm text-slate-500">৳{{ number_format($b->total_amount) }} · <span class="font-mono text-xs">#{{ $b->code }}</span></p>
                        @if ($b->status === 'pending_payment' && $b->payment_deadline_at)
                            <p class="mt-2 inline-block rounded-lg bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">Pay ৳{{ number_format($b->advance_amount) }} by {{ $b->payment_deadline_at->format('g:i A, d M') }}</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endforeach
    <div class="mt-8">{{ $bookings->links() }}</div>
@endif
@endsection
