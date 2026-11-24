@extends('layouts.app')
@section('title', 'Choose a new password')
@section('content')
<div class="mx-auto max-w-md">
    <form method="POST" action="{{ route('password.update') }}" class="card space-y-5 p-8 sm:p-10" x-data="{ show: false }">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <span class="icon-tile bg-brand-100 text-brand-700"><x-icon name="shield"/></span>
            <h1 class="mt-5 text-2xl font-bold tracking-tight">Choose a new password</h1>
            <p class="mt-1 text-slate-500">At least 6 characters.</p>
        </div>
        <div><label class="label" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" class="input" required autocomplete="email"></div>
        <div><label class="label" for="password">New password</label>
            <input id="password" :type="show ? 'text' : 'password'" type="password" name="password" class="input" required minlength="6" autofocus autocomplete="new-password"></div>
        <div><label class="label" for="password_confirmation">Repeat new password</label>
            <input id="password_confirmation" :type="show ? 'text' : 'password'" type="password" name="password_confirmation" class="input" required autocomplete="new-password"></div>
        <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" x-model="show" class="h-4 w-4 rounded border-slate-300 text-brand-600"> Show passwords</label>
        <button class="btn-primary btn-lg w-full">Save new password</button>
        <p class="text-center text-sm text-slate-500"><a href="{{ route('password.request') }}" class="font-semibold text-brand-700">Link expired? Get a new one</a></p>
    </form>
</div>
@endsection
