@extends('layouts.panel')
@section('title', 'Bookings')
@section('subtitle', 'Search any booking, check payments and mark games as played.')
@section('content')
<form class="card mb-6 grid gap-3 p-4 md:grid-cols-5">
    <input name="q" value="{{ request('q') }}" class="input md:col-span-2" placeholder="Code, phone, name or TrxID">
    <select name="status" class="input">
        <option value="">All statuses</option>
        @foreach (\App\Models\Booking::STATUS_LABELS as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach
    </select>
    @if ($turfs->count() > 1)
        <select name="turf" class="input">
            <option value="">All turfs</option>
            @foreach ($turfs as $t)<option value="{{ $t->id }}" @selected(request('turf') == $t->id)>{{ $t->name }}</option>@endforeach
        </select>
    @endif
    <input type="date" name="date" value="{{ request('date') }}" class="input">
    <div class="flex gap-2 md:col-span-5 md:justify-end">
        <button class="btn-primary">Filter</button>
        <a href="{{ route('panel.bookings.index') }}" class="btn-light">Reset</a>
    </div>
</form>

<div class="card overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-100">
        <thead class="bg-slate-50"><tr><th class="th">When</th><th class="th">Customer</th><th class="th">Amount</th><th class="th">Status</th><th class="th">Actions</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($bookings as $b)
                <tr>
                    <td class="td whitespace-nowrap"><p class="font-semibold">{{ $b->starts_at->format('d M Y') }}</p><p class="text-xs text-slate-500">{{ $b->starts_at->format('g:i A') }} – {{ $b->ends_at->format('g:i A') }}</p>@if ($turfs->count() > 1)<p class="text-xs text-slate-400">{{ $b->turf->name }}</p>@endif</td>
                    <td class="td">@include('panel.bookings.row-info')</td>
                    <td class="td whitespace-nowrap"><p class="font-semibold">৳{{ number_format($b->total_amount) }}</p><p class="text-xs text-slate-500">Paid ৳{{ number_format($b->paid_amount) }}</p></td>
                    <td class="td">@include('partials.status', ['booking' => $b])
                        @if ($b->status === 'cancelled')<p class="mt-1 max-w-[14rem] text-xs text-slate-400">{{ $b->cancel_reason }}</p>@endif</td>
                    <td class="td">@include('panel.bookings.actions')</td>
                </tr>
            @empty
                <tr><td colspan="5" class="td py-10 text-center text-slate-500">No bookings found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $bookings->links() }}</div>
@endsection
