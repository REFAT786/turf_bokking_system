<a href="{{ route('turfs.show', $turf) }}" class="group card card-hover flex flex-col overflow-hidden">
    <div class="relative aspect-[16/10] overflow-hidden bg-slate-200">
        <img src="{{ $turf->coverUrl() }}" alt="{{ $turf->name }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
        <div class="absolute left-3 top-3 flex flex-wrap gap-1.5">
            @isset($turf->distance_km)
                <span class="badge bg-white/95 text-slate-800 shadow-sm"><x-icon name="pin" class="h-3.5 w-3.5"/> {{ number_format($turf->distance_km, 1) }} km</span>
            @endisset
            @if ($turf->size)<span class="badge bg-black/55 text-white backdrop-blur">{{ $turf->size }}</span>@endif
        </div>
        @if ($turf->reviews_avg_rating)
            <span class="badge absolute right-3 top-3 bg-white/95 text-amber-600 shadow-sm">★ {{ number_format($turf->reviews_avg_rating, 1) }}</span>
        @endif
        <p class="absolute bottom-3 left-3 text-sm font-medium text-white/90 drop-shadow"><x-icon name="pin" class="-mt-0.5 inline h-4 w-4"/> {{ $turf->area }}, {{ $turf->city }}</p>
    </div>
    <div class="flex flex-1 flex-col p-4">
        <h3 class="font-semibold text-slate-900 group-hover:text-brand-700">{{ $turf->name }}</h3>
        @if ($turf->facilities)
            <p class="mt-1 line-clamp-1 text-xs text-slate-500">{{ collect($turf->facilities)->take(3)->map(fn ($f) => \App\Models\Turf::FACILITIES[$f] ?? $f)->join(' · ') }}</p>
        @endif
        <div class="mt-auto flex items-end justify-between pt-4">
            <p class="text-sm text-slate-500">from <span class="text-xl font-bold text-slate-900">৳{{ number_format($turf->minPrice()) }}</span>/hr</p>
            <span class="btn-primary btn-sm">Book</span>
        </div>
    </div>
</a>
