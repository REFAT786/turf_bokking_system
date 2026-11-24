<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#059669">
    <title>@yield('title', site('seo.title')) · {{ site('brand.name_1').site('brand.name_2') }}</title>
    <meta name="description" content="{{ site('seo.description') }}">
    <meta property="og:title" content="@yield('title', site('seo.title')) · {{ site('brand.name_1').site('brand.name_2') }}">
    <meta property="og:description" content="{{ site('seo.description') }}">
    <meta property="og:image" content="{{ site_image('hero.image') }}">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="flex min-h-screen flex-col bg-slate-50 font-sans text-slate-800 antialiased">
@php
    $me = auth()->user();
    $nav = [
        ['turfs.index', 'Find turfs'],
        ['how', 'How it works'],
    ];
@endphp
@if (\App\Support\SiteContent::on('announcement.enabled') && site('announcement.text'))
    @if (site('announcement.link'))
        <a href="{{ site('announcement.link') }}" class="block bg-gradient-to-r from-brand-700 via-brand-600 to-emerald-500 px-4 py-2 text-center text-sm font-medium text-white hover:brightness-110">
            {{ site('announcement.text') }} <span class="ml-1 underline underline-offset-2">Learn more →</span>
        </a>
    @else
        <div class="bg-gradient-to-r from-brand-700 via-brand-600 to-emerald-500 px-4 py-2 text-center text-sm font-medium text-white">{{ site('announcement.text') }}</div>
    @endif
@endif
<header class="sticky top-0 z-40 border-b border-slate-200/70 bg-white/85 backdrop-blur-lg" x-data="{ open: false }">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4">
        @include('partials.logo')

        <nav class="hidden items-center gap-1 text-sm font-medium md:flex">
            @foreach ($nav as [$r, $l])
                <a href="{{ route($r) }}" class="rounded-lg px-3 py-2 {{ request()->routeIs($r.'*') ? 'text-brand-700 bg-brand-50' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">{{ $l }}</a>
            @endforeach
            @guest
                <a href="{{ route('register.owner') }}" class="rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100 hover:text-slate-900">List your turf</a>
            @endguest
        </nav>

        <div class="hidden items-center gap-2 md:flex">
            @auth
                @if ($me->isCustomer())
                    <a href="{{ route('my.bookings') }}" class="btn-light btn-sm"><x-icon name="calendar" class="h-4 w-4"/> My bookings</a>
                @else
                    <a href="{{ $me->homeRoute() }}" class="btn-primary btn-sm"><x-icon name="home" class="h-4 w-4"/> Dashboard</a>
                @endif
                <div class="relative" x-data="{ m: false }" @click.outside="m = false">
                    <button @click="m = !m" class="grid h-9 w-9 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-800 ring-2 ring-white" aria-label="Account">{{ strtoupper(mb_substr($me->name, 0, 1)) }}</button>
                    <div x-show="m" x-cloak x-transition.origin.top.right class="absolute right-0 mt-2 w-56 rounded-2xl bg-white p-2 text-sm shadow-[var(--shadow-lift)] ring-1 ring-slate-200">
                        <p class="px-3 py-2"><span class="block font-semibold text-slate-900">{{ $me->name }}</span><span class="text-xs text-slate-500">{{ $me->phone }}</span></p>
                        <a href="{{ route('profile') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100"><x-icon name="user" class="h-4 w-4"/> My profile</a>
                        @if ($me->isCustomer())<a href="{{ route('my.bookings') }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-slate-600 hover:bg-slate-100"><x-icon name="calendar" class="h-4 w-4"/> My bookings</a>@endif
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-slate-600 hover:bg-slate-100"><x-icon name="logout" class="h-4 w-4"/> Log out</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn-ghost btn-sm">Log in</a>
                <a href="{{ route('register') }}" class="btn-primary btn-sm">Sign up free</a>
            @endauth
        </div>

        <button class="grid h-10 w-10 place-items-center rounded-xl ring-1 ring-slate-200 md:hidden" @click="open = !open" aria-label="Menu">
            <x-icon name="menu" x-show="!open"/><x-icon name="x" x-show="open" x-cloak/>
        </button>
    </div>

    <div x-show="open" x-cloak x-transition class="border-t border-slate-200 bg-white px-4 pb-5 pt-3 md:hidden">
        <div class="flex flex-col gap-1 text-base font-medium">
            @foreach ($nav as [$r, $l])<a href="{{ route($r) }}" class="rounded-xl px-3 py-2.5 hover:bg-slate-100">{{ $l }}</a>@endforeach
            @auth
                <a href="{{ $me->isCustomer() ? route('my.bookings') : $me->homeRoute() }}" class="rounded-xl px-3 py-2.5 hover:bg-slate-100">{{ $me->isCustomer() ? 'My bookings' : 'Dashboard' }}</a>
                <a href="{{ route('profile') }}" class="rounded-xl px-3 py-2.5 hover:bg-slate-100">My profile</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-xl px-3 py-2.5 text-left text-slate-500 hover:bg-slate-100">Log out</button></form>
            @else
                <a href="{{ route('register.owner') }}" class="rounded-xl px-3 py-2.5 hover:bg-slate-100">List your turf</a>
                <div class="mt-3 grid grid-cols-2 gap-2">
                    <a href="{{ route('login') }}" class="btn-light">Log in</a>
                    <a href="{{ route('register') }}" class="btn-primary">Sign up</a>
                </div>
            @endauth
        </div>
    </div>
</header>

<main class="flex-1">
    @hasSection('full')
        @if (session('success') || $errors->any())
            <div class="mx-auto max-w-7xl px-4 pt-6">@include('partials.flash')</div>
        @endif
        @yield('full')
    @else
        <div class="mx-auto max-w-7xl px-4 py-8 md:py-10">
            @include('partials.flash')
            @yield('content')
        </div>
    @endif
</main>

<footer class="mt-20 bg-ink text-slate-400">
    @php
        $phone = site('footer.phone');
        $email = site('footer.email');
        $fb = site('footer.facebook');
    @endphp
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 text-sm sm:grid-cols-2 lg:grid-cols-4">
        <div>
            @include('partials.logo', ['dark' => true])
            <p class="mt-4 max-w-sm leading-relaxed">{{ site('footer.about') }}</p>
        </div>
        <div>
            <p class="font-semibold text-white">For players</p>
            <ul class="mt-3 space-y-2">
                <li><a href="{{ route('turfs.index') }}" class="hover:text-white">Find a turf</a></li>
                <li><a href="{{ route('how') }}" class="hover:text-white">How booking works</a></li>
                <li><a href="{{ route('register') }}" class="hover:text-white">Create an account</a></li>
            </ul>
        </div>
        <div>
            <p class="font-semibold text-white">For turf owners</p>
            <ul class="mt-3 space-y-2">
                <li><a href="{{ route('register.owner') }}" class="hover:text-white">List your turf free</a></li>
                <li><a href="{{ route('login') }}" class="hover:text-white">Owner login</a></li>
            </ul>
        </div>
        <div>
            <p class="font-semibold text-white">Help & contact</p>
            <ul class="mt-3 space-y-2.5">
                @if ($phone)
                    <li><a href="tel:{{ $phone }}" class="flex items-start gap-2.5 hover:text-white"><x-icon name="phone" class="mt-0.5 h-4 w-4 shrink-0"/>
                        <span><span class="block text-xs text-slate-500">{{ site('brand.name_1').site('brand.name_2') }} helpline</span>{{ $phone }}</span></a></li>
                @endif
                @if ($email)
                    <li><a href="mailto:{{ $email }}" class="flex items-center gap-2.5 hover:text-white"><x-icon name="doc" class="h-4 w-4 shrink-0"/> {{ $email }}</a></li>
                @endif
                @if (site('footer.hours'))
                    <li class="flex items-center gap-2.5"><x-icon name="clock" class="h-4 w-4 shrink-0"/> {{ site('footer.hours') }}</li>
                @endif
                @if ($fb)
                    <li><a href="{{ $fb }}" target="_blank" rel="noopener" class="flex items-center gap-2.5 hover:text-white"><x-icon name="external" class="h-4 w-4 shrink-0"/> Facebook page</a></li>
                @endif
            </ul>
            <p class="mt-3 text-xs text-slate-500">For a booking, contact the turf directly from its page.</p>
        </div>
    </div>
    <div class="border-t border-white/10">
        <p class="mx-auto max-w-7xl px-4 py-5 text-xs">© {{ date('Y') }} {{ site('brand.name_1').site('brand.name_2') }} · {{ site('footer.copyright') }}</p>
    </div>
</footer>
@stack('scripts')
</body>
</html>
