@extends('layouts.panel')
@section('title', 'Turfs')
@section('subtitle', 'Approve new turfs or suspend ones with problems.')
@section('content')
<form class="card mb-6 flex flex-wrap gap-3 p-4">
    <input name="q" value="{{ request('q') }}" class="input max-w-xs" placeholder="Search turf">
    <select name="status" class="input max-w-xs"><option value="">All</option>@foreach (['pending', 'approved', 'suspended'] as $s)<option @selected(request('status') === $s)>{{ $s }}</option>@endforeach</select>
    <button class="btn-primary">Filter</button>
</form>
<div class="card overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-100">
        <thead class="bg-slate-50"><tr><th class="th">Turf</th><th class="th">Owner</th><th class="th">Price</th><th class="th">Bookings</th><th class="th">Approval</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($turfs as $t)
                <tr>
                    <td class="td"><div class="flex items-center gap-3"><img src="{{ $t->coverUrl() }}" alt="" class="h-11 w-16 shrink-0 rounded-lg object-cover">
                        <div><a href="{{ route('turfs.show', $t) }}" target="_blank" class="font-semibold hover:text-brand-600">{{ $t->name }}</a><p class="text-xs text-slate-500">{{ $t->area }}, {{ $t->city }}</p>
                        @php($st = $t->publicState())<span class="badge mt-1 {{ $st['color'] }}" title="{{ $st['hint'] }}">{{ $st['label'] }}</span></div></div></td>
                    <td class="td">{{ $t->owner->name }}<p class="text-xs text-slate-500">{{ $t->owner->phone }}</p>
                        @unless ($t->owner->isVerifiedOwner())<a href="{{ route('admin.owners', ['status' => 'all', 'q' => $t->owner->phone]) }}" class="badge bg-amber-100 text-amber-800">Owner not verified</a>@endunless</td>
                    <td class="td">৳{{ number_format($t->price_per_hour) }}</td>
                    <td class="td">{{ $t->bookings_count }}</td>
                    <td class="td">
                        <form method="POST" action="{{ route('admin.turfs.status', $t) }}">@csrf
                            <select name="status" class="input !py-1 text-xs" onchange="this.form.submit()">
                                @foreach (['pending', 'approved', 'suspended'] as $s)<option @selected($t->status === $s)>{{ $s }}</option>@endforeach
                            </select>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $turfs->links() }}</div>
@endsection
