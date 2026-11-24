@extends('layouts.panel')
@section('title', 'Account verification')
@section('subtitle', 'Your turfs go live once the admin has checked your documents.')
@section('content')
<div class="max-w-2xl space-y-6">
    @if ($owner->isVerifiedOwner())
        <div class="card p-6">
            <p class="text-lg font-semibold text-emerald-700">✓ Your account is verified</p>
            <p class="mt-1 text-sm text-slate-600">Verified on {{ $owner->verified_at?->format('d M Y') }}. Your approved turfs are visible to players and open for bookings.</p>
        </div>
    @elseif ($owner->verificationPending())
        <div class="card p-6">
            <p class="text-lg font-semibold text-amber-700">⏳ Documents under review</p>
            <p class="mt-1 text-sm text-slate-600">
                Submitted {{ $owner->verification_submitted_at?->diffForHumans() }}. You can keep setting up your turfs, prices and staff —
                they go live for players as soon as the admin verifies your account.
            </p>
        </div>
    @else
        <div class="card p-6">
            <p class="text-lg font-semibold text-rose-700">Verification {{ $owner->verificationRejected() ? 'rejected' : 'required' }}</p>
            @if ($owner->verification_note)
                <p class="mt-2 rounded-lg bg-rose-50 p-3 text-sm text-rose-800"><b>Reason:</b> {{ $owner->verification_note }}</p>
            @endif
            <p class="mt-2 text-sm text-slate-600">Upload your documents again below. Your turfs stay hidden from players until your account is verified.</p>
        </div>
    @endif

    @unless ($owner->isVerifiedOwner())
        <form method="POST" action="{{ route('panel.verification.store') }}" enctype="multipart/form-data" class="card space-y-4 p-6">
            @csrf
            <h2 class="font-semibold">{{ $owner->verificationPending() ? 'Replace submitted documents' : 'Submit documents' }}</h2>
            @include('partials.owner-documents', ['nid' => $owner->nid_number])
            <button class="btn-primary">Submit for review</button>
        </form>
    @endunless
</div>
@endsection
