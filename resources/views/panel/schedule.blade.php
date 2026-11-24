@extends('layouts.panel')
@section('title', 'Schedule & walk-ins')
@section('subtitle', 'See every hour of the day. Tap a free hour to add a phone or walk-in booking.')
@section('content')
<form class="card mb-6 flex flex-wrap items-end gap-3 p-4">
    <div><label class="label">Turf</label>
        <select name="turf" class="input" onchange="this.form.submit()">
            @foreach ($turfs as $t)<option value="{{ $t->id }}" @selected($t->id === $turf->id)>{{ $t->name }}</option>@endforeach
        </select>
    </div>
    <div><label class="label">Date</label><input type="date" name="date" value="{{ $date->toDateString() }}" class="input" onchange="this.form.submit()"></div>
    <div class="flex gap-2">
        <a href="{{ route('panel.schedule', ['turf' => $turf->id, 'date' => $date->copy()->subDay()->toDateString()]) }}" class="btn-light">←</a>
        <a href="{{ route('panel.schedule', ['turf' => $turf->id, 'date' => today()->toDateString()]) }}" class="btn-light">Today</a>
        <a href="{{ route('panel.schedule', ['turf' => $turf->id, 'date' => $date->copy()->addDay()->toDateString()]) }}" class="btn-light">→</a>
    </div>
</form>

<div class="grid gap-6 xl:grid-cols-3" x-data="slotPicker()">
    <div class="card overflow-hidden xl:col-span-2">
        <div class="border-b border-slate-100 p-4">
            <h2 class="font-semibold">{{ $date->format('l, d M Y') }}</h2>
            <p class="text-xs text-slate-500">Tap free slots to add a walk-in / phone booking.</p>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach ($slots as $i => $s)
                @php($b = $s['booking'])
                <div class="flex flex-col gap-2 p-3 sm:flex-row sm:items-center {{ $b ? '' : 'hover:bg-slate-50' }}">
                    <div class="w-36 shrink-0 text-sm font-semibold">{{ $s['start']->format('g:i A') }} – {{ $s['end']->format('g:i A') }}
                        <span class="block text-xs font-normal text-slate-400">৳{{ number_format($s['price']) }} {{ $s['night'] ? '🌙' : '' }}</span>
                    </div>
                    @if ($b)
                        <div class="flex-1">@include('panel.bookings.row-info')</div>
                        <div class="flex flex-col items-start gap-2 sm:items-end">
                            @include('partials.status', ['booking' => $b])
                            @include('panel.bookings.actions')
                        </div>
                    @elseif ($s['status'] === 'past')
                        <p class="flex-1 text-sm text-slate-400">— empty —</p>
                    @else
                        <div class="flex-1">
                            <button type="button" @click="toggle({{ $i }}, '{{ $s['start']->toDateTimeString() }}', {{ $s['price'] }})"
                                    :class="isSel({{ $i }}) ? 'bg-brand-600 text-white ring-brand-600' : 'ring-brand-500/40 text-brand-700'"
                                    class="rounded-lg px-3 py-1.5 text-sm font-semibold ring-1">
                                <span x-text="isSel({{ $i }}) ? '✓ Selected' : '+ Free'"></span>
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div>
        <div class="card sticky top-6 p-5">
            <h2 class="font-semibold">Add walk-in / phone booking</h2>
            <p x-show="!sel.length" class="mt-2 text-sm text-slate-500">Select one or more consecutive free slots on the left.</p>
            <form x-show="sel.length" x-cloak method="POST" action="{{ route('panel.bookings.store') }}" class="mt-3 space-y-3">
                @csrf
                <input type="hidden" name="turf_id" value="{{ $turf->id }}">
                <template x-for="s in sel"><input type="hidden" name="slots[]" :value="s.start"></template>
                <p class="rounded-lg bg-slate-50 p-3 text-sm"><b x-text="sel.length"></b> slot(s) · Total <b>৳<span x-text="total.toLocaleString()"></span></b></p>
                <div><label class="label">Customer name</label><input name="name" class="input" required></div>
                <div><label class="label">Mobile</label><input name="phone" class="input" placeholder="01XXXXXXXXX" required></div>
                <div><label class="label">Advance received now (৳)</label><input type="number" name="paid_amount" value="0" min="0" class="input"></div>
                <div><label class="label">Note</label><input name="notes" class="input"></div>
                <button class="btn-primary w-full">Save booking</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function slotPicker() {
    return {
        sel: [], total: 0,
        isSel(i) { return this.sel.some(s => s.i === i); },
        toggle(i, start, price) {
            const idx = this.sel.map(s => s.i);
            if (this.isSel(i)) {
                this.sel = (i === Math.min(...idx) || i === Math.max(...idx)) ? this.sel.filter(s => s.i !== i) : this.sel.filter(s => s.i < i);
            } else {
                const adjacent = !idx.length || i === Math.min(...idx) - 1 || i === Math.max(...idx) + 1;
                if (!adjacent) this.sel = [];
                if (this.sel.length >= 6) return;
                this.sel.push({ i, start, price });
                this.sel.sort((a, b) => a.i - b.i);
            }
            this.total = this.sel.reduce((t, s) => t + s.price, 0);
        },
    };
}
</script>
@endpush
