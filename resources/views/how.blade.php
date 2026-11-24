@extends('layouts.app')
@section('title', 'How it works')
@section('content')
@php($hours = \App\Models\Setting::get('payment_deadline_hours'))
<div class="mx-auto max-w-4xl">
    <div class="relative overflow-hidden rounded-[2rem] bg-brand-900 text-white">
        <img src="{{ site_image('how.image') }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-40">
        <div class="relative p-8 md:p-12">
            <p class="eyebrow !text-brand-200">Guide</p>
            <h1 class="mt-2 text-3xl font-bold md:text-4xl">{{ site('how.title') }}</h1>
            <p class="mt-3 max-w-lg text-white/80">{{ site('how.intro') }}</p>
        </div>
    </div>

    <ol class="relative mt-10 space-y-6 border-l-2 border-dashed border-brand-200 pl-8">
        @foreach ([
            ['search', 'Choose a turf & time', 'Search by area or tap “Turfs near me”. Open a turf, choose the date and tap one or more free (green) slots in a row.'],
            ['bolt', 'Reserve instantly', 'Your slot is held for you right away. Nobody else can book it while it\'s held.'],
            ['wallet', "Pay the advance within {$hours} hours", "Send the advance to the turf's bKash / Nagad / Rocket number shown on your booking, then enter the Transaction ID (TrxID). If no payment is submitted within {$hours} hours (or before the game starts), the booking is cancelled automatically."],
            ['check', 'Turf confirms', 'The turf checks its bKash/Nagad account and confirms. Your booking changes to “Confirmed”.'],
            ['pitch', 'Play & pay the rest', 'Show your booking code at the turf and pay the remaining amount in cash or mobile banking.'],
        ] as [$icon, $t, $d])
            <li class="relative">
                <span class="absolute -left-[3.15rem] grid h-10 w-10 place-items-center rounded-full bg-brand-600 text-white ring-4 ring-slate-50"><x-icon :name="$icon"/></span>
                <div class="card p-5">
                    <p class="text-xs font-semibold text-brand-600">Step {{ $loop->iteration }}</p>
                    <h3 class="mt-0.5 text-lg font-semibold">{{ $t }}</h3>
                    <p class="mt-1 text-slate-600">{{ $d }}</p>
                </div>
            </li>
        @endforeach
    </ol>

    <div class="mt-10 grid gap-5 md:grid-cols-2">
        <div class="card p-6">
            <span class="icon-tile bg-pink-100 text-pink-700"><x-icon name="wallet"/></span>
            <h3 class="mt-4 font-semibold">Why pay the turf directly?</h3>
            <p class="mt-1 text-sm text-slate-600">Your advance goes straight to the turf's own bKash/Nagad — no middleman and no extra gateway charge.</p>
        </div>
        <div class="card p-6">
            <span class="icon-tile bg-sky-100 text-sky-700"><x-icon name="clock"/></span>
            <h3 class="mt-4 font-semibold">Need to cancel?</h3>
            <p class="mt-1 text-sm text-slate-600">Cancel online up to {{ \App\Models\Setting::get('min_hours_before_cancel') }} hours before the game. Refunds of advances are handled by the turf.</p>
        </div>
    </div>

    <div class="mt-10 text-center">
        <a href="{{ route('turfs.index') }}" class="btn-primary btn-lg">Find a turf now <x-icon name="arrow-right" class="h-4 w-4"/></a>
    </div>
</div>
@endsection
