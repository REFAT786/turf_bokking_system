@extends($user->isCustomer() ? 'layouts.app' : 'layouts.panel')
@section('title', 'My profile')
@section('subtitle', 'Your account details and password.')
@section('content')
@php
    $roleName = ['super_admin' => 'Super admin', 'owner' => 'Turf owner', 'staff' => 'Staff', 'customer' => 'Player'][$user->role];
    $pw = $errors->getBag('password');
@endphp

<div class="mx-auto max-w-4xl space-y-6">
    {{-- ===== Header ===== --}}
    <div class="relative overflow-hidden rounded-[2rem] text-white">
        <img src="{{ asset('images/art/banner-'.($user->isCustomer() ? 'player' : ($user->isSuperAdmin() ? 'admin' : 'owner')).'.svg') }}" alt="" class="absolute inset-0 h-full w-full object-cover">
        <div class="relative flex flex-col gap-5 p-6 sm:flex-row sm:items-center md:p-8">
            <span class="grid h-20 w-20 shrink-0 place-items-center rounded-3xl bg-white/95 text-3xl font-bold text-brand-700 shadow-lg">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
            <div class="min-w-0 flex-1">
                <span class="badge bg-white/20 text-white ring-1 ring-white/30">{{ $roleName }}</span>
                <h1 class="mt-2 truncate text-2xl font-bold md:text-3xl">{{ $user->name }}</h1>
                <p class="mt-1 text-sm text-white/80">{{ $user->phone }} · {{ $user->email }} · member since {{ $user->created_at->format('M Y') }}</p>
            </div>
        </div>
    </div>

    @if ($stats)
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([['calendar', 'Upcoming games', $stats['upcoming'], 'bg-sky-100 text-sky-700'], ['check', 'Games played', $stats['played'], 'bg-brand-100 text-brand-700'], ['pitch', 'Turfs played at', $stats['turfs'], 'bg-amber-100 text-amber-700']] as [$icon, $label, $value, $tone])
                <a href="{{ route('my.bookings') }}" class="card card-hover flex items-center gap-4 p-5">
                    <span class="icon-tile {{ $tone }}"><x-icon :name="$icon"/></span>
                    <div><p class="text-sm text-slate-500">{{ $label }}</p><p class="text-2xl font-bold">{{ $value }}</p></div>
                </a>
            @endforeach
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-5">
        {{-- ===== Details ===== --}}
        <form method="POST" action="{{ route('profile.update') }}" class="card lg:col-span-3">
            @csrf @method('PUT')
            <div class="card-head">
                <div class="flex items-center gap-3"><span class="icon-tile !h-10 !w-10 bg-brand-100 text-brand-700"><x-icon name="user"/></span>
                    <div><h2 class="card-title">Your details</h2><p class="text-xs text-slate-500">Turfs see your name and mobile on your bookings.</p></div></div>
            </div>
            <div class="grid gap-5 p-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="label" for="name">Full name</label><input id="name" name="name" value="{{ old('name', $user->name) }}" class="input" required autocomplete="name"></div>
                @if ($user->isOwner())
                    <div class="sm:col-span-2"><label class="label" for="business_name">Business name</label><input id="business_name" name="business_name" value="{{ old('business_name', $user->business_name) }}" class="input" required></div>
                @endif
                <div><label class="label" for="phone">Mobile number <span class="text-rose-500">*</span></label><input id="phone" name="phone" value="{{ old('phone', $user->phone) }}" class="input" required inputmode="tel" autocomplete="tel"><p class="hint">You log in with this number.</p></div>
                <div><label class="label" for="email">Email <span class="text-rose-500">*</span></label><input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="input" required autocomplete="email"><p class="hint">Used if you forget your password.</p></div>
            </div>
            <div class="flex justify-end border-t border-slate-100 p-5"><button class="btn-primary">Save details</button></div>
        </form>

        {{-- ===== Password ===== --}}
        <form method="POST" action="{{ route('profile.password') }}" class="card lg:col-span-2" x-data="{ show: false }">
            @csrf @method('PUT')
            <div class="card-head">
                <div class="flex items-center gap-3"><span class="icon-tile !h-10 !w-10 bg-violet-100 text-violet-700"><x-icon name="shield"/></span>
                    <div><h2 class="card-title">Change password</h2><p class="text-xs text-slate-500">At least 6 characters.</p></div></div>
            </div>
            <div class="space-y-4 p-5">
                @if ($pw->any())
                    <ul class="rounded-xl bg-rose-50 p-3 text-sm text-rose-800 ring-1 ring-rose-200">@foreach ($pw->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                @endif
                <div><label class="label" for="current_password">Current password</label>
                    <input id="current_password" :type="show ? 'text' : 'password'" type="password" name="current_password" class="input" required autocomplete="current-password"></div>
                <div><label class="label" for="password">New password</label>
                    <input id="password" :type="show ? 'text' : 'password'" type="password" name="password" class="input" required minlength="6" autocomplete="new-password"></div>
                <div><label class="label" for="password_confirmation">Repeat new password</label>
                    <input id="password_confirmation" :type="show ? 'text' : 'password'" type="password" name="password_confirmation" class="input" required autocomplete="new-password"></div>
                <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" x-model="show" class="h-4 w-4 rounded border-slate-300 text-brand-600"> Show passwords</label>
            </div>
            <div class="flex justify-end border-t border-slate-100 p-5"><button class="btn-dark">Change password</button></div>
        </form>
    </div>
</div>
@endsection
