@extends('layouts.panel')
@section('title', 'Income reports')
@section('subtitle', 'How much you booked and collected each month.')
@section('actions')
<form><input type="month" name="month" value="{{ $month->format('Y-m') }}" class="input" onchange="this.form.submit()"></form>
@endsection
@section('content')
@php($tot = fn ($k) => $byTurf->sum($k))
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ([['Booked value', '৳'.number_format($tot('revenue'))], ['Collected', '৳'.number_format($tot('collected'))], ['Games', $tot('bookings').' ('.$tot('hours').' hrs)'], ['Cancelled / no-show', $tot('cancelled').' / '.$tot('no_show')]] as [$l, $v])
        <div class="card p-5"><p class="text-sm text-slate-500">{{ $l }}</p><p class="mt-1 text-2xl font-bold">{{ $v }}</p></div>
    @endforeach
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    <div class="card overflow-x-auto xl:col-span-2">
        <h2 class="border-b border-slate-100 p-4 font-semibold">By turf — {{ $month->format('F Y') }}</h2>
        <table class="min-w-full divide-y divide-slate-100">
            <thead class="bg-slate-50"><tr><th class="th">Turf</th><th class="th">Games</th><th class="th">Online / walk-in</th><th class="th">Booked value</th><th class="th">Collected</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($byTurf as $r)
                    <tr><td class="td font-semibold">{{ $r['turf']->name }}</td><td class="td">{{ $r['bookings'] }} ({{ $r['hours'] }}h)</td><td class="td">{{ $r['online'] }} / {{ $r['walk_in'] }}</td><td class="td">৳{{ number_format($r['revenue']) }}</td><td class="td">৳{{ number_format($r['collected']) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card p-4">
        <h2 class="font-semibold">Received by method</h2>
        <div class="mt-3 space-y-2">
            @forelse ($byMethod as $m)
                <div class="flex justify-between text-sm"><span class="capitalize">{{ $m->payment_method }} ({{ $m->n }})</span><b>৳{{ number_format($m->total) }}</b></div>
            @empty
                <p class="text-sm text-slate-500">No payments this month.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="card mt-6 p-4">
    <h2 class="font-semibold">Daily booked value</h2>
    @php($max = max(1, $daily->max('revenue')))
    <div class="mt-4 flex h-48 items-end gap-1">
        @for ($d = $month->copy(); $d->month === $month->month; $d->addDay())
            @php($row = $daily->firstWhere('d', $d->toDateString()))
            <div class="group relative flex-1">
                <div class="rounded-t bg-brand-500/80 hover:bg-brand-600" style="height: {{ $row ? max(4, round($row->revenue / $max * 180)) : 2 }}px"></div>
                <div class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 whitespace-nowrap rounded bg-slate-900 px-2 py-1 text-xs text-white group-hover:block">{{ $d->format('d M') }}: ৳{{ number_format($row->revenue ?? 0) }}</div>
            </div>
        @endfor
    </div>
    <div class="mt-1 flex justify-between text-xs text-slate-400"><span>1</span><span>{{ $month->daysInMonth }}</span></div>
</div>
@endsection
