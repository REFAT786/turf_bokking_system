@extends('layouts.panel')
@section('title', 'Users')
@section('subtitle', 'Everyone with an account. Disable an account to block its login.')
@section('content')
<form class="card mb-6 flex flex-wrap gap-3 p-4">
    <input name="q" value="{{ request('q') }}" class="input max-w-xs" placeholder="Name, phone, email">
    <select name="role" class="input max-w-xs"><option value="">All roles</option>@foreach (['owner', 'staff', 'customer', 'super_admin'] as $r)<option @selected(request('role') === $r)>{{ $r }}</option>@endforeach</select>
    <button class="btn-primary">Filter</button>
</form>
<p class="mb-4 flex items-start gap-2 rounded-2xl bg-sky-50 p-4 text-sm text-sky-900 ring-1 ring-sky-200"><x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0"/>
    <span>Someone forgot their password? They can use <b>“Forgot password?”</b> on the login page (reset link by email). If they can't reach their email, check who they are by phone, then use <b>Set password</b> and tell them the new one privately.</span></p>
<div class="card overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-100">
        <thead class="bg-slate-50"><tr><th class="th">User</th><th class="th">Role</th><th class="th">Joined</th><th class="th"></th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($users as $u)
                <tr>
                    <td class="td"><p class="font-semibold">{{ $u->name }}</p><p class="text-xs text-slate-500">{{ $u->phone }} · {{ $u->email }}</p></td>
                    <td class="td"><span class="badge bg-slate-100 text-slate-700">{{ str_replace('_', ' ', $u->role) }}</span>
                        @if ($u->business_name)<p class="text-xs text-slate-500">{{ $u->business_name }}</p>@endif
                        @if ($u->employer)<p class="text-xs text-slate-500">at {{ $u->employer->business_name ?? $u->employer->name }}</p>@endif</td>
                    <td class="td">{{ $u->created_at->format('d M Y') }}</td>
                    <td class="td text-right" x-data="{ pw: false }">
                        @unless ($u->id === auth()->id())
                            <div class="flex justify-end gap-2">
                                <button type="button" @click="pw = !pw" class="btn-light btn-sm"><x-icon name="shield" class="h-3.5 w-3.5"/> Set password</button>
                                <form method="POST" action="{{ route('admin.users.toggle', $u) }}">@csrf<button class="btn-light btn-sm {{ $u->is_active ? 'text-rose-600' : '' }}">{{ $u->is_active ? 'Disable' : 'Enable' }}</button></form>
                            </div>
                            <form x-show="pw" x-cloak method="POST" action="{{ route('admin.users.password', $u) }}" class="ml-auto mt-2 flex max-w-xs gap-2">@csrf
                                <input name="password" class="input !py-1.5 text-sm" minlength="6" required placeholder="New password (min 6)" autocomplete="off">
                                <button class="btn-primary btn-sm shrink-0">Save</button>
                            </form>
                        @else
                            <a href="{{ route('profile') }}" class="text-xs font-semibold text-brand-700">My profile →</a>
                        @endunless
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
