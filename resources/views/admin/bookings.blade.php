@extends('layouts.panel')
@section('title', 'All bookings')
@section('subtitle', 'Every booking on the platform, for support and checking.')
@section('content')
<form class="card mb-6 flex flex-wrap gap-3 p-4">
    <input name="q" value="{{ request('q') }}" class="input max-w-xs" placeholder="Code or phone">
    <select name="status" class="input max-w-xs"><option value="">All statuses</option>@foreach (\App\Models\Booking::STATUS_LABELS as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select>
    <button class="btn-primary">Filter</button>
</form>
<div class="card overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-100">
        <thead class="bg-slate-50"><tr><th class="th">Code</th><th class="th">Turf</th><th class="th">When</th><th class="th">Customer</th><th class="th">Amount</th><th class="th">Status</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($bookings as $b)
                <tr>
                    <td class="td font-mono">{{ $b->code }}</td>
                    <td class="td">{{ $b->turf->name }}</td>
                    <td class="td whitespace-nowrap">{{ $b->starts_at->format('d M, g:i A') }}</td>
                    <td class="td">{{ $b->customer_name }}<p class="text-xs text-slate-500">{{ $b->customer_phone }}</p></td>
                    <td class="td">৳{{ number_format($b->total_amount) }}<p class="text-xs text-slate-500">paid ৳{{ number_format($b->paid_amount) }}</p></td>
                    <td class="td">@include('partials.status', ['booking' => $b])</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $bookings->links() }}</div>
@endsection
