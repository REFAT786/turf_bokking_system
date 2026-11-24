@extends('layouts.panel')
@section('title', 'Settings')
@section('subtitle', 'Booking rules that apply to every turf.')
@section('content')
<form method="POST" action="{{ route('admin.settings.save') }}" class="mx-auto max-w-3xl space-y-6">
    @csrf
    <div class="card p-6">
        <div class="flex items-center gap-3"><span class="icon-tile !h-10 !w-10 bg-amber-100 text-amber-700"><x-icon name="clock"/></span><h2 class="text-lg font-semibold">Booking rules</h2></div>
        <div class="mt-6 grid gap-6 sm:grid-cols-3">
            <div>
                <label class="label">Payment deadline</label>
                <div class="relative"><input type="number" name="payment_deadline_hours" min="1" max="72" value="{{ $settings['payment_deadline_hours'] }}" class="input !pr-16"><span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">hours</span></div>
                <p class="hint">Unpaid bookings are cancelled after this (never later than kick-off).</p>
            </div>
            <div>
                <label class="label">Book ahead up to</label>
                <div class="relative"><input type="number" name="max_advance_booking_days" min="1" max="60" value="{{ $settings['max_advance_booking_days'] }}" class="input !pr-14"><span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">days</span></div>
                <p class="hint">How far into the future players can book.</p>
            </div>
            <div>
                <label class="label">Online cancel until</label>
                <div class="relative"><input type="number" name="min_hours_before_cancel" min="0" max="72" value="{{ $settings['min_hours_before_cancel'] }}" class="input !pr-16"><span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">hours</span></div>
                <p class="hint">Hours before the game a player can still cancel.</p>
            </div>
        </div>
    </div>
    <div class="card p-6">
        <div class="flex items-center gap-3"><span class="icon-tile !h-10 !w-10 bg-sky-100 text-sky-700"><x-icon name="shield"/></span><h2 class="text-lg font-semibold">Listings</h2></div>
        <div class="mt-6 space-y-5">
            <label class="flex cursor-pointer items-start gap-3 rounded-2xl p-4 ring-1 ring-slate-200">
                <input type="checkbox" name="auto_approve_turfs" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600" @checked($settings['auto_approve_turfs'] === '1')>
                <span class="text-sm"><b class="block text-slate-900">Auto-approve new turfs</b><span class="text-slate-500">Skip manual turf review. Owners still need to be verified before anything goes live.</span></span>
            </label>
            <p class="text-sm text-slate-500">Support phone, email and all website texts are now edited in <a href="{{ route('admin.content', ['tab' => 'footer']) }}" class="font-semibold text-brand-700">Website content</a>.</p>
        </div>
    </div>
    <div class="flex justify-end"><button class="btn-primary btn-lg">Save settings</button></div>
</form>
@endsection
