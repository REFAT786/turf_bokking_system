@extends('layouts.app')
@section('title', site('seo.title'))

@section('full')
{{-- ===== Hero ===== --}}
<section class="relative isolate overflow-hidden bg-brand-950 text-white">
    <img src="{{ site_image('hero.image') }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-70">
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-brand-950 via-brand-950/85 to-brand-950/20"></div>

    <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-16 md:py-24 lg:grid-cols-5">
        <div class="lg:col-span-3">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-brand-200 ring-1 ring-white/15">
                <span class="h-2 w-2 animate-pulse rounded-full bg-brand-400"></span> Live slots · {{ $stats['turfs'] }} turfs in {{ $stats['areas'] }} areas
            </span>
            <h1 class="mt-5 text-4xl font-extrabold leading-[1.1] tracking-tight sm:text-5xl lg:text-6xl">
                {{ site('hero.title') }}<br><span class="bg-gradient-to-r from-brand-300 to-lime-200 bg-clip-text text-transparent">{{ site('hero.highlight') }}</span>
            </h1>
            <p class="mt-5 max-w-xl text-lg text-white/75">{{ site('hero.subtitle') }}</p>

            <form action="{{ route('turfs.index') }}" class="mt-8 flex max-w-xl items-center gap-2 rounded-2xl bg-white p-2 shadow-2xl shadow-black/30">
                <x-icon name="search" class="ml-3 h-5 w-5 shrink-0 text-slate-400"/>
                <input name="q" class="w-full border-0 bg-transparent py-2.5 text-base text-slate-800 placeholder:text-slate-400 focus:outline-none" placeholder="{{ site('hero.search_placeholder') }}">
                <button class="btn-primary shrink-0 !rounded-xl px-5 py-3">Search</button>
            </form>
            <div class="mt-4 flex flex-wrap items-center gap-3">
                @include('partials.nearby-button', ['class' => 'btn bg-white/10 text-white ring-1 ring-white/20 hover:bg-white/20'])
                @foreach ($areas->take(4) as $a)
                    <a href="{{ route('turfs.index', ['q' => $a->area]) }}" class="rounded-full px-3 py-1.5 text-sm text-white/70 ring-1 ring-white/15 hover:bg-white/10 hover:text-white">{{ $a->area }}</a>
                @endforeach
            </div>
        </div>

        {{-- Why-book card (general benefits — no specific times or prices) --}}
        @php($cardPoints = \App\Support\SiteContent::lines('hero.card_points'))
        @if (\App\Support\SiteContent::offerActive())
            <div class="lg:col-span-2">
                <div class="overflow-hidden rounded-3xl bg-white text-slate-800 shadow-2xl shadow-black/40 ring-1 ring-white/40">
                    @if (site_image('offer.image'))
                        <img src="{{ site_image('offer.image') }}" alt="" class="aspect-[16/7] w-full object-cover">
                    @endif
                    <div class="p-6">
                        @if (site('offer.badge'))
                            <span class="badge bg-amber-100 text-amber-800"><x-icon name="bolt" class="h-3.5 w-3.5"/> {{ site('offer.badge') }}</span>
                        @endif
                        <p class="mt-3 text-2xl font-bold leading-snug text-slate-900">{{ site('offer.title') }}</p>
                        @if (site('offer.text'))<p class="mt-2 whitespace-pre-line text-slate-600">{{ site('offer.text') }}</p>@endif
                        @if (\App\Support\SiteContent::stored('offer.ends_at'))
                            <p class="mt-3 flex items-center gap-1.5 text-xs font-semibold text-rose-600"><x-icon name="clock" class="h-4 w-4"/> Ends {{ \Carbon\Carbon::parse(\App\Support\SiteContent::stored('offer.ends_at'))->format('d M Y') }}</p>
                        @endif
                        @if (site('offer.button') && site('offer.link'))
                            <a href="{{ site('offer.link') }}" class="btn-primary mt-5 w-full">{{ site('offer.button') }} <x-icon name="arrow-right" class="h-4 w-4"/></a>
                        @endif
                    </div>
                </div>
            </div>
        @elseif ($cardPoints)
            <div class="hidden lg:col-span-2 lg:block">
                <div class="rounded-3xl bg-white/10 p-6 ring-1 ring-white/15 backdrop-blur-md">
                    <p class="text-lg font-semibold">{{ site('hero.card_title') }}</p>
                    <ul class="mt-5 space-y-3">
                        @foreach ($cardPoints as $i => $point)
                            <li class="flex items-center gap-3 rounded-2xl bg-white/10 p-3 ring-1 ring-white/10">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-500/90 text-white">
                                    <x-icon :name="['clock', 'bolt', 'wallet', 'shield', 'check', 'star'][$i % 6]" class="h-5 w-5"/>
                                </span>
                                <span class="text-sm font-medium text-white/90">{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>

    {{-- Stats strip --}}
    @if (\App\Support\SiteContent::on('hero.show_stats'))
    <div class="border-t border-white/10 bg-black/20 backdrop-blur">
        <dl class="mx-auto grid max-w-7xl grid-cols-2 gap-4 px-4 py-6 text-center md:grid-cols-4">
            @foreach ([['Live turfs', $stats['turfs']], ['Areas covered', $stats['areas']], ['Players', $stats['players']], ['Games booked', $stats['games']]] as [$l, $v])
                <div><dt class="text-xs uppercase tracking-wider text-white/50">{{ $l }}</dt><dd class="mt-1 text-2xl font-bold">{{ number_format($v) }}</dd></div>
            @endforeach
        </dl>
    </div>
    @endif
</section>

{{-- ===== Popular turfs ===== --}}
<section class="mx-auto max-w-7xl px-4 pt-16">
    <div class="flex items-end justify-between gap-4">
        <div>
            <p class="eyebrow">{{ site('home.popular_eyebrow') }}</p>
            <h2 class="mt-1 text-3xl font-bold tracking-tight">{{ site('home.popular_title') }}</h2>
        </div>
        <a href="{{ route('turfs.index') }}" class="btn-light btn-sm">View all <x-icon name="arrow-right" class="h-4 w-4"/></a>
    </div>
    @if ($turfs->isEmpty())
        <div class="card mt-8 p-10 text-center">
            <img src="{{ asset('images/art/turf-1.svg') }}" alt="" class="mx-auto h-32 rounded-2xl">
            <p class="mt-4 font-semibold">No turfs listed yet.</p>
            <a class="btn-primary mt-4" href="{{ route('register.owner') }}">Be the first to list yours</a>
        </div>
    @else
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($turfs as $turf) @include('partials.turf-card') @endforeach
        </div>
    @endif
</section>

{{-- ===== How it works ===== --}}
<section class="mx-auto max-w-7xl px-4 pt-20">
    <div class="text-center">
        <p class="eyebrow">{{ site('home.how_eyebrow') }}</p>
        <h2 class="mt-1 text-3xl font-bold tracking-tight">{{ site('home.how_title') }}</h2>
    </div>
    <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['search', site('home.step1_title'), site('home.step1_text'), 'bg-sky-100 text-sky-700'],
            ['clock', site('home.step2_title'), site('home.step2_text'), 'bg-brand-100 text-brand-700'],
            ['wallet', site('home.step3_title'), site('home.step3_text'), 'bg-pink-100 text-pink-700'],
            ['check', site('home.step4_title'), site('home.step4_text'), 'bg-amber-100 text-amber-700'],
        ] as [$icon, $title, $text, $tone])
            <div class="card relative p-6">
                <span class="absolute right-5 top-4 text-5xl font-black text-slate-100">{{ $loop->iteration }}</span>
                <span class="icon-tile {{ $tone }}"><x-icon :name="$icon"/></span>
                <h3 class="relative mt-4 font-semibold text-slate-900">{{ $title }}</h3>
                <p class="relative mt-1 text-sm text-slate-600">{{ $text }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ===== Browse by area ===== --}}
@if ($areas->isNotEmpty())
<section class="mx-auto max-w-7xl px-4 pt-20">
    <h2 class="text-2xl font-bold tracking-tight">{{ site('home.areas_title') }}</h2>
    <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ($areas as $a)
            <a href="{{ route('turfs.index', ['q' => $a->area]) }}" class="group relative overflow-hidden rounded-2xl">
                <img src="{{ asset('images/art/turf-'.(($loop->index % 8) + 1).'.svg') }}" alt="" class="aspect-[4/3] w-full object-cover transition duration-300 group-hover:scale-110">
                <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent"></div>
                <div class="absolute inset-x-3 bottom-2.5 text-white">
                    <p class="font-semibold leading-tight">{{ $a->area }}</p>
                    <p class="text-xs text-white/70">{{ $a->city }}</p>
                </div>
            </a>
        @endforeach
    </div>
</section>
@endif

{{-- ===== FAQ ===== --}}
@php($faq = \App\Support\SiteContent::faq())
@if ($faq)
<section class="mx-auto max-w-3xl px-4 pt-20">
    <h2 class="text-center text-3xl font-bold tracking-tight">{{ site('faq.title') }}</h2>
    <div class="mt-8 space-y-3" x-data="{ open: 0 }">
        @foreach ($faq as $i => $item)
            <div class="card overflow-hidden">
                <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}" class="flex w-full items-center justify-between gap-4 p-5 text-left font-semibold text-slate-900">
                    {{ $item['q'] }}
                    <x-icon name="plus" class="h-5 w-5 shrink-0 text-brand-600 transition" ::class="open === {{ $i }} && 'rotate-45'"/>
                </button>
                <div x-show="open === {{ $i }}" x-cloak x-transition class="px-5 pb-5 text-slate-600">{{ $item['a'] }}</div>
            </div>
        @endforeach
    </div>
</section>
@endif

{{-- ===== Owner CTA ===== --}}
<section class="mx-auto max-w-7xl px-4 pt-20">
    <div class="relative isolate overflow-hidden rounded-[2rem] bg-brand-900 text-white">
        <img src="{{ site_image('cta.image') }}" alt="" class="absolute inset-y-0 right-0 -z-10 hidden h-full w-1/2 object-cover md:block">
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-brand-900 via-brand-900/95 to-brand-900/30"></div>
        <div class="max-w-xl p-8 md:p-14">
            <p class="eyebrow !text-brand-300">{{ site('cta.eyebrow') }}</p>
            <h2 class="mt-2 text-3xl font-bold tracking-tight md:text-4xl">{{ site('cta.title') }}</h2>
            <p class="mt-4 text-white/75">{{ site('cta.text') }}</p>
            <ul class="mt-6 grid gap-3 text-sm sm:grid-cols-2">
                @foreach (\App\Support\SiteContent::lines('cta.points') as $f)
                    <li class="flex items-center gap-2"><span class="grid h-5 w-5 place-items-center rounded-full bg-brand-500"><x-icon name="check" class="h-3.5 w-3.5"/></span>{{ $f }}</li>
                @endforeach
            </ul>
            <a href="{{ route('register.owner') }}" class="btn mt-8 bg-white px-6 py-3 text-brand-800 hover:bg-brand-50">{{ site('cta.button') }} <x-icon name="arrow-right" class="h-4 w-4"/></a>
        </div>
    </div>
</section>
@endsection
