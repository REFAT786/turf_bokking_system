@extends('layouts.app')
@section('title', 'Booking '.$booking->code)
@section('content')
@php $turf = $booking->turf; @endphp
<a href="{{ route('my.bookings') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-900"><x-icon name="arrow-left" class="h-4 w-4"/> All bookings</a>

@php
    $steps = ['Reserved', 'Advance sent', 'Confirmed', 'Played'];
    $at = ['pending_payment' => 0, 'payment_submitted' => 1, 'confirmed' => 2, 'completed' => 3][$booking->status] ?? null;
@endphp
<div class="relative mt-4 overflow-hidden rounded-[2rem] text-white">
    <img src="{{ $turf->coverUrl() }}" alt="" class="absolute inset-0 h-full w-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/55 to-black/20"></div>
    <div class="relative p-6 md:p-8">
        <p class="text-sm text-white/75">{{ $booking->starts_at->format('l, d M Y') }} · {{ $booking->starts_at->format('g:i A') }} – {{ $booking->ends_at->format('g:i A') }}</p>
        <h1 class="mt-1 text-2xl font-bold md:text-3xl">{{ $turf->name }}</h1>
        @if ($at !== null)
            <ol class="mt-6 grid max-w-xl grid-cols-4 gap-2 text-xs">
                @foreach ($steps as $k => $label)
                    <li>
                        <div class="h-1.5 rounded-full {{ $k <= $at ? 'bg-brand-400' : 'bg-white/25' }}"></div>
                        <p class="mt-2 {{ $k <= $at ? 'font-semibold text-white' : 'text-white/60' }}">{{ $label }}</p>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div class="card p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-wider text-slate-500">Booking code</p>
                    <p class="font-mono text-2xl font-bold tracking-widest">{{ $booking->code }}</p>
                </div>
                @include('partials.status')
            </div>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div><p class="text-xs text-slate-500">Turf</p><a href="{{ route('turfs.show', $turf) }}" class="font-semibold hover:text-brand-600">{{ $turf->name }}</a><p class="text-sm text-slate-500">{{ $turf->area }}, {{ $turf->city }}</p></div>
                <div><p class="text-xs text-slate-500">When</p><p class="font-semibold">{{ $booking->starts_at->format('l, d M Y') }}</p><p class="text-sm text-slate-500">{{ $booking->starts_at->format('g:i A') }} – {{ $booking->ends_at->format('g:i A') }} ({{ $booking->hours() }} hr)</p></div>
            </div>
            <div class="mt-5 grid grid-cols-3 gap-3 rounded-xl bg-slate-50 p-4 text-center">
                <div><p class="text-xs text-slate-500">Total</p><p class="font-bold">৳{{ number_format($booking->total_amount) }}</p></div>
                <div><p class="text-xs text-slate-500">Paid</p><p class="font-bold text-brand-700">৳{{ number_format($booking->paid_amount) }}</p></div>
                <div><p class="text-xs text-slate-500">Due at venue</p><p class="font-bold">৳{{ number_format($booking->dueAmount()) }}</p></div>
            </div>
            @if ($booking->status === 'cancelled')
                <p class="mt-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ $booking->cancel_reason }}</p>
            @endif
            @if ($booking->status === 'confirmed')
                <p class="mt-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">✅ You're all set! Show code <b>{{ $booking->code }}</b> at the turf and pay ৳{{ number_format($booking->dueAmount()) }} there.</p>
            @endif
            @if ($booking->status === 'payment_submitted')
                <p class="mt-4 rounded-lg bg-sky-50 p-3 text-sm text-sky-800">⏳ We sent your TrxID <b>{{ $booking->trx_id }}</b> to the turf. They will confirm after checking their {{ ucfirst($booking->payment_method) }} account. Your slot stays reserved meanwhile.</p>
            @endif
        </div>

        @if ($booking->status === 'pending_payment')
            @php $accounts = $turf->paymentAccounts(); @endphp
            <div class="card p-6" x-data="{ left: {{ max(0, now()->diffInSeconds($booking->payment_deadline_at, false)) }}, method: '{{ array_key_first($accounts) }}' }"
                 x-init="setInterval(() => { if (left > 0) left--; else location.reload() }, 1000)">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-lg font-semibold">Pay advance to confirm</h2>
                    <p class="rounded-lg bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800">
                        ⏱ <span x-text="String(Math.floor(left/3600)).padStart(2,'0') + ':' + String(Math.floor(left%3600/60)).padStart(2,'0') + ':' + String(left%60).padStart(2,'0')"></span> left
                    </p>
                </div>
                <p class="mt-1 text-sm text-slate-600">Send <b>৳{{ number_format($booking->advance_amount) }}</b> before <b>{{ $booking->payment_deadline_at->format('g:i A, d M') }}</b>. Otherwise this booking is cancelled automatically.</p>

                <ol class="mt-4 space-y-2 text-sm text-slate-700">
                    <li>1. Open your bKash / Nagad / Rocket app → <b>Send Money</b></li>
                    <li>2. Send <b>৳{{ number_format($booking->advance_amount) }}</b> to the turf's number below. Use reference <b>{{ $booking->code }}</b>.</li>
                    <li>3. Enter the Transaction ID (TrxID) from the SMS below.</li>
                </ol>

                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    @foreach ($accounts as $m => $num)
                        <label class="cursor-pointer rounded-xl p-3 ring-2 transition" :class="method === '{{ $m }}' ? 'ring-brand-600 bg-brand-50' : 'ring-slate-200'">
                            <input type="radio" class="sr-only" value="{{ $m }}" x-model="method">
                            <p class="text-xs font-semibold uppercase {{ ['bkash' => 'text-pink-600', 'nagad' => 'text-orange-600', 'rocket' => 'text-purple-600'][$m] }}">{{ $m }}</p>
                            <p class="font-mono text-lg font-bold" x-data>{{ $num }}</p>
                            <button type="button" class="text-xs text-brand-600" @click.prevent="navigator.clipboard.writeText('{{ $num }}'); $el.textContent='Copied!'">Copy number</button>
                        </label>
                    @endforeach
                </div>
                @if ($turf->payment_note)<p class="mt-3 text-sm text-slate-500">ℹ️ {{ $turf->payment_note }}</p>@endif

                <form method="POST" action="{{ route('my.bookings.payment', $booking) }}" class="mt-5 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <input type="hidden" name="payment_method" :value="method">
                    <div><label class="label">Amount sent (৳)</label><input type="number" name="amount" value="{{ old('amount', $booking->advance_amount) }}" min="{{ $booking->advance_amount }}" class="input" required></div>
                    <div><label class="label">Sent from number</label><input name="payer_number" value="{{ old('payer_number', $booking->customer_phone) }}" class="input" required></div>
                    <div class="sm:col-span-2"><label class="label">Transaction ID (TrxID)</label><input name="trx_id" value="{{ old('trx_id') }}" class="input font-mono uppercase" placeholder="e.g. BHK7X2LMQ9" required></div>
                    <button class="btn-primary sm:col-span-2 py-3">Submit payment</button>
                </form>
            </div>
        @endif

        @if (($booking->status === 'completed' || ($booking->status === 'confirmed' && $booking->ends_at->isPast())))
            <div class="card p-6">
                <h2 class="text-lg font-semibold">{{ $booking->review ? 'Your review' : 'How was the game?' }}</h2>
                <form method="POST" action="{{ route('my.bookings.review', $booking) }}" class="mt-3 space-y-3" x-data="{ r: {{ $booking->review->rating ?? 0 }} }">
                    @csrf
                    <input type="hidden" name="rating" :value="r">
                    <div class="flex gap-1 text-3xl">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button" @click="r={{ $i }}" :class="r >= {{ $i }} ? 'text-amber-500' : 'text-slate-300'">★</button>
                        @endfor
                    </div>
                    <textarea name="comment" class="input" rows="3" placeholder="Pitch quality, lights, staff…">{{ $booking->review->comment ?? '' }}</textarea>
                    <button class="btn-primary" :disabled="!r">Save review</button>
                </form>
            </div>
        @endif
    </div>

    <aside class="space-y-4">
        <div class="card p-5 text-sm">
            <h3 class="font-semibold">Need help?</h3>
            <p class="mt-1 text-slate-600">Contact {{ $turf->name }} directly.</p>
            <div class="mt-3 grid grid-cols-2 gap-2">
                <a href="tel:{{ $turf->phone }}" class="btn-light btn-sm">📞 Call</a>
                <a href="{{ $turf->whatsappLink("Booking {$booking->code} on ".$booking->starts_at->format('d M g:i A')) }}" target="_blank" rel="noopener" class="btn btn-sm bg-[#25D366] text-white">WhatsApp</a>
            </div>
            @if ($turf->latitude)
                <a href="https://www.google.com/maps/dir/?api=1&destination={{ $turf->latitude }},{{ $turf->longitude }}" target="_blank" rel="noopener" class="btn-light btn-sm mt-2 w-full">🧭 Directions</a>
            @endif
        </div>
        @if ($booking->canBeCancelledByCustomer())
            <form method="POST" action="{{ route('my.bookings.cancel', $booking) }}" class="card p-5" x-data="{ sure: false }">
                @csrf
                <h3 class="font-semibold">Can't make it?</h3>
                <p class="mt-1 text-sm text-slate-600">Cancel so others can play. @if ($booking->paid_amount) Refund of your advance is handled by the turf. @endif</p>
                <button type="button" x-show="!sure" @click="sure=true" class="btn-light btn-sm mt-3 w-full text-rose-600">Cancel booking</button>
                <div x-show="sure" x-cloak class="mt-3 space-y-2">
                    <input name="reason" class="input" placeholder="Reason (optional)">
                    <button class="btn-danger btn-sm w-full">Yes, cancel it</button>
                </div>
            </form>
        @endif
    </aside>
</div>
@endsection
