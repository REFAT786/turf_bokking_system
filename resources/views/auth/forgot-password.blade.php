@extends('layouts.app')
@section('title', 'Forgot password')
@section('content')
<div class="mx-auto max-w-md">
    <div class="card p-8 sm:p-10">
        <span class="icon-tile bg-violet-100 text-violet-700"><x-icon name="shield"/></span>
        <h1 class="mt-5 text-2xl font-bold tracking-tight">Forgot your password?</h1>
        <p class="mt-1 text-slate-500">Enter your mobile number or email. We'll email you a link to choose a new password.</p>

        @if (session('status'))
            <div class="mt-6 flex gap-3 rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-900 ring-1 ring-emerald-200">
                <x-icon name="check" class="mt-0.5 h-5 w-5 shrink-0"/><p>{{ session('status') }} Check your inbox and spam folder.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label class="label" for="login">Mobile number or email</label>
                <input id="login" name="login" value="{{ old('login') }}" class="input" placeholder="01712345678" required autofocus autocomplete="username">
            </div>
            <button class="btn-primary btn-lg w-full">Send reset link</button>
        </form>

        <div class="mt-8 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600 ring-1 ring-slate-200">
            <p class="font-semibold text-slate-800">No access to that email?</p>
            <p class="mt-1">Call our helpline{{ site('footer.phone') ? ' on '.site('footer.phone') : '' }} and we'll reset it for you after checking your details.
                Staff can also ask their turf owner.</p>
        </div>
        <p class="mt-6 text-center text-sm text-slate-500"><a href="{{ route('login') }}" class="font-semibold text-brand-700">← Back to log in</a></p>
    </div>
</div>
@endsection
