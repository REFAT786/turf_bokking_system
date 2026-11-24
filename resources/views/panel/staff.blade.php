@extends('layouts.panel')
@section('title', 'Staff')
@section('subtitle', 'Give your manager or counter staff their own login, and choose which turfs each one handles.')
@section('content')
@if ($turfs->isEmpty())
    <div class="card p-10 text-center">
        <span class="icon-tile mx-auto bg-slate-100 text-slate-500"><x-icon name="pitch"/></span>
        <p class="mt-3 font-semibold">Add a turf first</p>
        <p class="mt-1 text-sm text-slate-500">Staff are linked to turfs, so add at least one turf before creating staff logins.</p>
        <a href="{{ route('panel.turfs.create') }}" class="btn-primary mt-4"><x-icon name="plus" class="h-4 w-4"/> Add a turf</a>
    </div>
@else
<div class="grid gap-6 lg:grid-cols-3">
    {{-- ===== Team ===== --}}
    <div class="space-y-4 lg:col-span-2">
        <div class="flex items-start gap-3 rounded-2xl bg-sky-50 p-4 text-sm text-sky-900 ring-1 ring-sky-200">
            <x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0"/>
            <p>Staff only see the <b>turfs you tick</b> for them — one or several. They can view the schedule, check payments, add walk-ins and mark games played there.
                They <b>cannot</b> edit turfs, prices, staff or see income reports.</p>
        </div>

        @forelse ($staff as $s)
            @php $mine = $s->assignedTurfs->pluck('id')->all(); @endphp
            <div class="card p-5" x-data="{ editing: {{ $errors->any() && old('editing') == $s->id ? 'true' : 'false' }} }">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-brand-100 font-bold text-brand-800">{{ strtoupper(mb_substr($s->name, 0, 1)) }}</span>
                        <div>
                            <p class="font-semibold">{{ $s->name }}
                                <span class="badge ml-1 align-middle {{ $s->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">{{ $s->is_active ? 'Active' : 'Disabled' }}</span></p>
                            <p class="text-xs text-slate-500">{{ $s->phone }} · {{ $s->email }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="editing = !editing" class="btn-light btn-sm"><x-icon name="user" class="h-4 w-4"/> Edit</button>
                        <form method="POST" action="{{ route('panel.staff.toggle', $s) }}">@csrf<button class="btn-light btn-sm">{{ $s->is_active ? 'Disable' : 'Enable' }}</button></form>
                        <form method="POST" action="{{ route('panel.staff.destroy', $s) }}">@csrf @method('DELETE')<button class="btn-light btn-sm text-rose-600" onclick="return confirm('Remove {{ $s->name }}? Their login will stop working.')">Remove</button></form>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Responsible for {{ count($mine) }} {{ Str::plural('turf', count($mine)) }}</p>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @forelse ($s->assignedTurfs as $t)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-3 py-1 text-sm font-medium text-brand-800 ring-1 ring-brand-200"><x-icon name="pitch" class="h-4 w-4"/> {{ $t->name }}</span>
                        @empty
                            <span class="rounded-full bg-amber-50 px-3 py-1 text-sm text-amber-800 ring-1 ring-amber-200">No turf yet — this person can't see anything. Click “Edit”.</span>
                        @endforelse
                    </div>
                </div>

                @php $isEditing = old('editing') == $s->id; @endphp
                <form x-show="editing" x-cloak x-transition method="POST" action="{{ route('panel.staff.update', $s) }}" class="mt-4 space-y-4 rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                    @csrf @method('PUT')
                    <input type="hidden" name="editing" value="{{ $s->id }}">
                    <div>
                        <p class="text-sm font-semibold">Details</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div><label class="label">Name</label><input name="name" value="{{ $isEditing ? old('name') : $s->name }}" class="input" required></div>
                            <div><label class="label">Mobile <span class="text-rose-500">*</span> <span class="font-normal text-slate-400">(login)</span></label><input name="phone" value="{{ $isEditing ? old('phone') : $s->phone }}" class="input" required></div>
                            <div><label class="label">Email <span class="text-rose-500">*</span></label><input type="email" name="email" value="{{ $isEditing ? old('email') : $s->email }}" class="input" required></div>
                            <div x-data="{ show: false }"><label class="label">New password <span class="font-normal text-slate-400">(optional)</span></label>
                                <div class="relative"><input :type="show ? 'text' : 'password'" type="password" name="password" class="input !pr-14" minlength="6" placeholder="Leave empty to keep current" autocomplete="new-password">
                                    <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-semibold text-slate-500" x-text="show ? 'Hide' : 'Show'">Show</button></div>
                            </div>
                        </div>
                    </div>
                    <p class="text-sm font-semibold">Which turfs does {{ strtok($s->name, ' ') }} handle?</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ($turfs as $t)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl bg-white p-3 text-sm ring-1 ring-slate-200 has-[:checked]:bg-brand-50 has-[:checked]:ring-brand-300">
                                <input type="checkbox" name="turfs[]" value="{{ $t->id }}" class="h-4 w-4 rounded border-slate-300 text-brand-600" @checked(in_array($t->id, $isEditing ? old('turfs', []) : $mine))>
                                <span><b class="block font-medium text-slate-900">{{ $t->name }}</b><span class="text-xs text-slate-500">{{ $t->area }}</span></span>
                            </label>
                        @endforeach
                    </div>
                    <div class="flex gap-2">
                        <button class="btn-primary btn-sm">Save changes</button>
                        <button type="button" @click="editing = false" class="btn-ghost btn-sm">Cancel</button>
                    </div>
                </form>
            </div>
        @empty
            <div class="card p-10 text-center">
                <span class="icon-tile mx-auto bg-slate-100 text-slate-500"><x-icon name="users"/></span>
                <p class="mt-3 font-semibold">No staff yet</p>
                <p class="mt-1 text-sm text-slate-500">Running the turf alone? That's fine — you don't need staff.</p>
            </div>
        @endforelse
    </div>

    {{-- ===== Add staff ===== --}}
    <form method="POST" action="{{ route('panel.staff.store') }}" class="card h-fit space-y-4 p-5 lg:sticky lg:top-24">
        @csrf
        <h2 class="card-title">Add manager / staff</h2>
        <div><label class="label">Name</label><input name="name" value="{{ old('editing') ? '' : old('name') }}" class="input" required></div>
        <div><label class="label">Mobile <span class="text-rose-500">*</span> <span class="font-normal text-slate-400">(used to log in)</span></label><input name="phone" value="{{ old('editing') ? '' : old('phone') }}" class="input" placeholder="01XXXXXXXXX" required></div>
        <div><label class="label">Email <span class="text-rose-500">*</span></label><input type="email" name="email" value="{{ old('editing') ? '' : old('email') }}" class="input" placeholder="name@gmail.com" required><p class="hint">Lets them reset a forgotten password.</p></div>
        <div><label class="label">Password</label><input name="password" class="input" required minlength="6"><p class="hint">Share it with them; at least 6 characters.</p></div>
        <div>
            <p class="label">Responsible for</p>
            <div class="space-y-2">
                @foreach ($turfs as $t)
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl p-2.5 text-sm ring-1 ring-slate-200 has-[:checked]:bg-brand-50 has-[:checked]:ring-brand-300">
                        <input type="checkbox" name="turfs[]" value="{{ $t->id }}" class="h-4 w-4 rounded border-slate-300 text-brand-600"
                               @checked(! old('editing') && in_array($t->id, old('turfs', $turfs->count() === 1 ? [$t->id] : [])))>
                        <span class="font-medium text-slate-800">{{ $t->name }}</span>
                    </label>
                @endforeach
            </div>
            <p class="hint">Tick one or more turfs.</p>
        </div>
        <button class="btn-primary w-full">Create staff login</button>
    </form>
</div>
@endif
@endsection
