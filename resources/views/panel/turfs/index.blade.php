@extends('layouts.panel')
@section('title', 'My turfs')
@section('subtitle', 'Your listings, photos, prices and whether players can see them.')
@section('actions')<a href="{{ route('panel.turfs.create') }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4"/> Add turf</a>@endsection
@section('content')
<div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
    @forelse ($turfs as $turf)
        @php($state = $turf->publicState())
        <div class="card flex flex-col overflow-hidden">
            <div class="relative">
                <img src="{{ $turf->coverUrl() }}" class="aspect-[16/9] w-full object-cover" alt="">
                <span class="badge absolute left-3 top-3 shadow-sm {{ $state['color'] }}">
                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span> {{ $state['label'] }}
                </span>
                @unless ($turf->hasOwnPhoto())
                    <a href="{{ route('panel.turfs.edit', $turf) }}#photos" class="badge absolute bottom-3 left-3 bg-white/95 text-slate-800 shadow-sm hover:bg-white"><x-icon name="image" class="h-3.5 w-3.5"/> Add real photos</a>
                @endunless
                <span class="badge absolute bottom-3 right-3 bg-black/60 text-white">{{ count($turf->photoUrls()) }} {{ Str::plural('photo', count($turf->photoUrls())) }}</span>
            </div>
            <div class="flex flex-1 flex-col p-5">
                <h3 class="text-lg font-semibold">{{ $turf->name }}</h3>
                <p class="text-sm text-slate-500">{{ $turf->area }}, {{ $turf->city }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ $state['hint'] }}</p>
                <dl class="mt-4 grid grid-cols-3 gap-2 rounded-xl bg-slate-50 p-3 text-center text-sm">
                    <div><dt class="text-xs text-slate-500">Day</dt><dd class="font-semibold">৳{{ number_format($turf->price_per_hour) }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Night</dt><dd class="font-semibold">{{ $turf->night_price_per_hour ? '৳'.number_format($turf->night_price_per_hour) : '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Bookings</dt><dd class="font-semibold">{{ $turf->bookings_count }}</dd></div>
                </dl>
                <div class="mt-4 grid grid-cols-3 gap-2 pt-1">
                    <a href="{{ route('panel.turfs.edit', $turf) }}" class="btn-light btn-sm">Edit</a>
                    <a href="{{ route('panel.schedule', ['turf' => $turf->id]) }}" class="btn-light btn-sm">Schedule</a>
                    <a href="{{ route('turfs.show', $turf) }}" target="_blank" class="btn-light btn-sm">View <x-icon name="external" class="h-3.5 w-3.5"/></a>
                </div>
            </div>
        </div>
    @empty
        <div class="card grid items-center overflow-hidden md:col-span-2 md:grid-cols-2 xl:col-span-3">
            <img src="{{ asset('images/art/turf-4.svg') }}" alt="" class="h-full w-full object-cover">
            <div class="p-8">
                <p class="text-xl font-semibold">No turfs yet</p>
                <p class="mt-1 text-slate-500">Add your turf's details once. Players will see it as soon as it's approved.</p>
                <a href="{{ route('panel.turfs.create') }}" class="btn-primary mt-5"><x-icon name="plus" class="h-4 w-4"/> Add your first turf</a>
            </div>
        </div>
    @endforelse
</div>
@endsection
