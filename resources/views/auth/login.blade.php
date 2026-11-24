@extends('layouts.app')
@section('title', 'Log in')
@section('content')
<div class="mx-auto grid max-w-5xl overflow-hidden rounded-[2rem] bg-white shadow-[var(--shadow-lift)] ring-1 ring-slate-200/70 md:grid-cols-2">
    <div class="relative hidden md:block">
        <img src="{{ asset('images/art/auth.svg') }}" alt="" class="absolute inset-0 h-full w-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-brand-950/90 via-brand-950/30 to-transparent"></div>
        <div class="absolute inset-x-8 bottom-8 text-white">
            <p class="text-2xl font-bold leading-snug">Your next game is<br>one tap away.</p>
            <p class="mt-2 text-sm text-white/75">Players, turf owners, staff and admins all log in here.</p>
        </div>
    </div>
    <div class="p-8 sm:p-12">
        <h1 class="text-3xl font-bold tracking-tight">Welcome back</h1>
        <p class="mt-1 text-slate-500">Log in with your mobile number or email.</p>
        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
            @csrf
            <div>
                <label class="label" for="login">Mobile number or email</label>
                <div class="relative">
                    <x-icon name="user" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"/>
                    <input id="login" name="login" value="{{ old('login') }}" class="input !pl-10" placeholder="01712345678" required autofocus autocomplete="username">
                </div>
            </div>
            <div x-data="{ show: false }">
                <label class="label" for="password">Password</label>
                <div class="relative">
                    <input id="password" :type="show ? 'text' : 'password'" type="password" name="password" class="input !pr-16" required autocomplete="current-password">
                    <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500 hover:text-slate-800" x-text="show ? 'Hide' : 'Show'">Show</button>
                </div>
            </div>
            <div class="flex items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-brand-600"> Keep me logged in</label>
                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">Forgot password?</a>
            </div>
            <button class="btn-primary btn-lg w-full">Log in</button>
        </form>
        <div class="mt-8 grid gap-3 border-t border-slate-100 pt-6 text-sm sm:grid-cols-2">
            <a href="{{ route('register') }}" class="flex items-center gap-3 rounded-2xl p-3 ring-1 ring-slate-200 hover:ring-brand-400">
                <span class="icon-tile !h-9 !w-9 bg-sky-100 text-base">⚽</span><span><b class="block text-slate-900">New player?</b><span class="text-slate-500">Sign up free</span></span>
            </a>
            <a href="{{ route('register.owner') }}" class="flex items-center gap-3 rounded-2xl p-3 ring-1 ring-slate-200 hover:ring-brand-400">
                <span class="icon-tile !h-9 !w-9 bg-brand-100 text-base">🏟️</span><span><b class="block text-slate-900">Own a turf?</b><span class="text-slate-500">List it free</span></span>
            </a>
        </div>
    </div>
</div>
@endsection
