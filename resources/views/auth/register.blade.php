@extends('layouts.app')
@section('title', $asOwner ? 'List your turf' : 'Sign up')
@section('content')
<div class="mx-auto grid max-w-6xl items-start gap-10 lg:grid-cols-5">
    {{-- Left: pitch --}}
    <div class="lg:col-span-2 lg:sticky lg:top-24">
        <div class="relative overflow-hidden rounded-[2rem]">
            <img src="{{ asset($asOwner ? 'images/art/turf-2.svg' : 'images/art/turf-1.svg') }}" alt="" class="aspect-[4/3] w-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
            <p class="absolute inset-x-6 bottom-5 text-2xl font-bold leading-snug text-white">
                {{ $asOwner ? 'Get online bookings without phone calls.' : 'Book turfs in seconds.' }}
            </p>
        </div>
        <ul class="mt-6 space-y-3 text-sm text-slate-700">
            @foreach ($asOwner
                ? ['Live slot schedule & walk-in bookings', 'Players pay the advance straight to your bKash/Nagad', 'Staff logins for your manager', 'Unpaid bookings cancel automatically', 'Monthly income reports']
                : ['See free hours at every turf, live', 'Reserve instantly — no phone calls', 'Get your booking code right away', 'Track all your games in one place'] as $f)
                <li class="flex items-center gap-3"><span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-100 text-brand-700"><x-icon name="check" class="h-4 w-4"/></span>{{ $f }}</li>
            @endforeach
        </ul>
        @if ($asOwner)
            <div class="mt-6 flex gap-3 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
                <x-icon name="shield" class="h-5 w-5 shrink-0"/>
                <p><b>Verified owners only.</b> Keep your NID and trade license (or other business proof) ready. You can set up your turf right away — it goes live once we verify your documents.</p>
            </div>
        @endif
    </div>

    {{-- Right: form --}}
    <div class="card p-6 sm:p-10 lg:col-span-3">
        <h1 class="text-3xl font-bold tracking-tight">{{ $asOwner ? 'List your turf — free' : 'Create your account' }}</h1>
        <p class="mt-1 text-slate-500">{{ $asOwner ? 'Create an owner account. It takes about 3 minutes.' : 'Free forever. Takes 20 seconds.' }}</p>

        <p class="mt-6 text-sm font-medium text-slate-600">I want to sign up as a</p>
        <div class="mt-2 grid grid-cols-2 gap-2 rounded-2xl bg-slate-100 p-1.5 text-sm font-semibold">
            <a href="{{ route('register') }}" class="rounded-xl py-2.5 text-center transition {{ $asOwner ? 'text-slate-500 hover:text-slate-800' : 'bg-white text-brand-700 shadow-sm' }}">⚽ Player</a>
            <a href="{{ route('register.owner') }}" class="rounded-xl py-2.5 text-center transition {{ $asOwner ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">🏟️ Turf owner</a>
        </div>

        <form method="POST" action="{{ $asOwner ? route('register.owner.store') : route('register.store') }}" @if ($asOwner) enctype="multipart/form-data" @endif class="mt-6 space-y-5">
            @csrf
            @if ($asOwner)<p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Step 1 · About you</p>@endif
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label class="label">Full name</label><input name="name" value="{{ old('name') }}" class="input" required autocomplete="name"></div>
                @if ($asOwner)
                    <div><label class="label">Business / turf company name</label><input name="business_name" value="{{ old('business_name') }}" class="input" required></div>
                @endif
                <div><label class="label">Mobile number <span class="text-rose-500">*</span></label><input name="phone" value="{{ old('phone') }}" class="input" placeholder="01712345678" required autocomplete="tel" inputmode="tel"><p class="hint">You'll log in with this number.</p></div>
                <div class="{{ $asOwner ? '' : 'sm:col-span-2' }}"><label class="label">Email <span class="text-rose-500">*</span></label><input type="email" name="email" value="{{ old('email') }}" class="input" placeholder="name@gmail.com" required autocomplete="email"><p class="hint">Needed to reset your password — use one you can open.</p></div>
                <div><label class="label">Password</label><input type="password" name="password" class="input" required minlength="6" autocomplete="new-password"><p class="hint">At least 6 characters.</p></div>
                <div><label class="label">Confirm password</label><input type="password" name="password_confirmation" class="input" required autocomplete="new-password"></div>
            </div>
            @if ($asOwner)
                <p class="pt-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Step 2 · Verification documents</p>
                @include('partials.owner-documents')
            @endif
            <button class="btn-primary btn-lg w-full">{{ $asOwner ? 'Create owner account' : 'Create account' }}</button>
        </form>
        <p class="mt-6 text-center text-sm text-slate-500">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-brand-700">Log in</a></p>
    </div>
</div>
@endsection
