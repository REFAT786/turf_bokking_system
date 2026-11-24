@php
    $u = auth()->user();
    $business = $u->businessOwner();

    // Sidebar: [route, label, icon, badge count]
    if ($u->isSuperAdmin()) {
        $pendingOwners = \App\Models\User::where('role', 'owner')->where('verification_status', \App\Models\User::VERIFICATION_PENDING)->count();
        $pendingTurfs = \App\Models\Turf::where('status', 'pending')->count();
        $groups = [
            'Overview' => [['admin.dashboard', 'Dashboard', 'home', 0]],
            'Approvals' => [
                ['admin.owners', 'Verify owners', 'shield', $pendingOwners],
                ['admin.turfs', 'Turfs', 'pitch', $pendingTurfs],
            ],
            'Platform' => [
                ['admin.bookings', 'All bookings', 'calendar', 0],
                ['admin.users', 'Users', 'users', 0],
                ['admin.content', 'Website content', 'image', 0],
                ['admin.settings', 'Booking rules', 'settings', 0],
            ],
        ];
        $roleLabel = 'Super admin';
    } else {
        $toVerify = \App\Models\Booking::whereIn('turf_id', $u->managedTurfs()->select('id'))->where('status', 'payment_submitted')->count();
        $groups = [
            'Daily work' => [
                ['panel.dashboard', 'Dashboard', 'home', 0],
                ['panel.schedule', 'Schedule & walk-ins', 'calendar', 0],
                ['panel.bookings.index', 'Bookings', 'list', $toVerify],
            ],
        ];
        if ($u->isOwner()) {
            $groups['My business'] = [
                ['panel.turfs.index', 'My turfs', 'pitch', 0],
                ['panel.staff.index', 'Staff', 'users', 0],
                ['panel.reports', 'Income reports', 'chart', 0],
                ['panel.verification', 'Verification', 'shield', $business->isVerifiedOwner() ? 0 : 1],
            ];
        }
        $roleLabel = $u->isOwner() ? ($u->business_name ?: 'Turf owner') : 'Staff · '.($u->employer?->business_name ?? '');
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b1220">
    <title>@yield('title', 'Dashboard') · {{ site('brand.name_1').site('brand.name_2') }}</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-slate-100/70 font-sans text-slate-800 antialiased" x-data="{ nav: false }">
<div class="flex min-h-screen">
    {{-- ===== Sidebar ===== --}}
    <aside class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-ink text-slate-400 transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
           :class="nav && '!translate-x-0'">
        <div class="flex h-16 items-center justify-between px-5">
            @include('partials.logo', ['dark' => true])
            <button class="grid h-9 w-9 place-items-center rounded-lg text-slate-400 hover:bg-white/10 lg:hidden" @click="nav = false" aria-label="Close menu"><x-icon name="x"/></button>
        </div>

        <a href="{{ route('profile') }}" title="My profile" class="mx-4 mb-2 flex items-center gap-3 rounded-2xl bg-white/5 p-3 transition hover:bg-white/10">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-600 font-bold text-white">{{ strtoupper(mb_substr($u->name, 0, 1)) }}</span>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-white">{{ $u->name }}</p>
                <p class="truncate text-xs">{{ $roleLabel }}</p>
            </div>
        </a>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
            @foreach ($groups as $group => $links)
                <div>
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[.14em] text-slate-500">{{ $group }}</p>
                    <div class="space-y-0.5">
                        @foreach ($links as [$route, $label, $icon, $count])
                            @php($active = request()->routeIs($route.'*') || ($route === 'panel.turfs.index' && request()->routeIs('panel.turfs.*')))
                            <a href="{{ route($route) }}"
                               class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $active ? 'bg-brand-600 text-white shadow-lg shadow-brand-900/40' : 'hover:bg-white/5 hover:text-white' }}">
                                <x-icon :name="$icon" class="h-5 w-5 {{ $active ? '' : 'text-slate-500 group-hover:text-slate-300' }}"/>
                                <span class="flex-1">{{ $label }}</span>
                                @if ($count)
                                    <span class="min-w-6 rounded-full px-2 py-0.5 text-center text-xs font-bold {{ $active ? 'bg-white text-brand-700' : 'bg-amber-400 text-amber-950' }}">{{ $route === 'panel.verification' ? '!' : $count }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        <div class="space-y-1 border-t border-white/10 p-3 text-sm">
            <a href="{{ route('profile') }}" class="flex items-center gap-3 rounded-xl px-3 py-2 {{ request()->routeIs('profile') ? 'bg-white/10 text-white' : 'hover:bg-white/5 hover:text-white' }}"><x-icon name="user" class="h-5 w-5 text-slate-500"/> My profile</a>
            <a href="{{ route('home') }}" target="_blank" class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-white/5 hover:text-white"><x-icon name="external" class="h-5 w-5 text-slate-500"/> View website</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="flex w-full items-center gap-3 rounded-xl px-3 py-2 hover:bg-white/5 hover:text-white"><x-icon name="logout" class="h-5 w-5 text-slate-500"/> Log out</button>
            </form>
        </div>
    </aside>
    <div x-show="nav" x-cloak x-transition.opacity @click="nav = false" class="fixed inset-0 z-30 bg-black/50 lg:hidden"></div>

    {{-- ===== Main ===== --}}
    <div class="min-w-0 flex-1">
        <header class="sticky top-0 z-20 border-b border-slate-200/70 bg-white/85 backdrop-blur-lg">
            <div class="flex min-h-16 flex-wrap items-center justify-between gap-3 px-4 py-3 md:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button class="grid h-10 w-10 shrink-0 place-items-center rounded-xl ring-1 ring-slate-200 lg:hidden" @click="nav = true" aria-label="Open menu"><x-icon name="menu"/></button>
                    <div class="min-w-0">
                        <h1 class="truncate text-lg font-bold text-slate-900">@yield('title', 'Dashboard')</h1>
                        @hasSection('subtitle')<p class="hidden truncate text-sm text-slate-500 sm:block">@yield('subtitle')</p>@endif
                    </div>
                </div>
                <div class="flex items-center gap-2">@yield('actions')</div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl p-4 md:p-8">
            @if ($business && ! $business->isVerifiedOwner() && ! request()->routeIs('panel.verification'))
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl px-5 py-4 text-sm ring-1 {{ $business->verificationRejected() ? 'bg-rose-50 text-rose-800 ring-rose-200' : 'bg-amber-50 text-amber-900 ring-amber-200' }}">
                    <span class="flex items-start gap-3">
                        <x-icon name="shield" class="mt-0.5 h-5 w-5 shrink-0"/>
                        <span>
                            @if ($business->verificationRejected())
                                <b>Verification rejected.</b> {{ $business->verification_note }}
                            @elseif ($business->verificationPending())
                                <b>Account under review.</b> Your turfs are hidden from players until the admin verifies the owner's documents. You can keep setting things up.
                            @else
                                <b>Verification required.</b> Upload the owner's documents so your turfs can go live.
                            @endif
                        </span>
                    </span>
                    @if ($u->isOwner())
                        <a href="{{ route('panel.verification') }}" class="btn-light btn-sm">{{ $business->verificationPending() ? 'View status' : 'Upload documents' }}</a>
                    @endif
                </div>
            @endif
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>
@stack('scripts')
</body>
</html>
