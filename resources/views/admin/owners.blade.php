@extends('layouts.panel')
@section('title', 'Verify owners')
@section('subtitle', 'Compare each owner\'s selfie with their NID photo, check the business proof, then approve.')
@section('content')
@php
    $tabs = ['pending' => 'Awaiting review', 'approved' => 'Verified', 'rejected' => 'Rejected', 'all' => 'All owners'];
    $badge = ['pending' => 'bg-amber-100 text-amber-800', 'approved' => 'bg-emerald-100 text-emerald-800', 'rejected' => 'bg-rose-100 text-rose-700'];
    $checks = [
        'face' => 'The face in the selfie matches the NID photo',
        'card' => 'The card in the selfie is the same NID (front)',
        'name' => 'Name on the NID matches the account name',
        'number' => 'NID number matches the number typed',
        'business' => 'Business proof matches the business name',
    ];
@endphp

<div class="mb-4 flex flex-wrap gap-2">
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.owners', ['status' => $key]) }}" class="{{ $status === $key ? 'btn-primary' : 'btn-light' }} btn-sm">{{ $label }}</a>
    @endforeach
</div>

<form class="card mb-6 flex flex-wrap gap-3 p-4">
    <input type="hidden" name="status" value="{{ $status }}">
    <div class="relative min-w-60 flex-1">
        <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"/>
        <input name="q" value="{{ request('q') }}" class="input !pl-10" placeholder="Name, business, phone or NID number">
    </div>
    <button class="btn-primary">Search</button>
</form>

<div class="space-y-6">
    @forelse ($owners as $o)
        @php
            $doc = fn ($key) => route('admin.owners.document', [$o, $key]);
        @endphp
        <div class="card overflow-hidden" x-data="{ rejecting: false, zoom: null, checked: [] }">
            {{-- Header --}}
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 p-5">
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 place-items-center rounded-full bg-slate-100 font-bold text-slate-600">{{ strtoupper(mb_substr($o->name, 0, 1)) }}</span>
                    <div>
                        <p class="text-lg font-semibold">{{ $o->name }}
                            <span class="badge align-middle {{ $badge[$o->verification_status] ?? 'bg-slate-100 text-slate-700' }}">{{ $o->verification_status ? ucfirst($o->verification_status) : 'No documents' }}</span>
                            @unless ($o->is_active)<span class="badge bg-slate-200 align-middle text-slate-700">Disabled</span>@endunless
                        </p>
                        <p class="text-sm text-slate-600">{{ $o->business_name }} · {{ $o->turfs_count }} {{ Str::plural('turf', $o->turfs_count) }}</p>
                        <p class="text-xs text-slate-500">{{ $o->phone }} · {{ $o->email }}
                            @if ($o->verification_submitted_at) · submitted {{ $o->verification_submitted_at->diffForHumans() }} @endif
                            @if ($o->verified_at) · verified {{ $o->verified_at->format('d M Y') }} @endif</p>
                    </div>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-2 text-right ring-1 ring-slate-200">
                    <p class="text-[11px] uppercase tracking-wide text-slate-500">NID number typed</p>
                    <p class="font-mono text-lg font-semibold tracking-wider">{{ $o->nid_number ?? '—' }}</p>
                </div>
            </div>

            <div class="grid gap-6 p-5 lg:grid-cols-5">
                {{-- Face match: NID front vs selfie --}}
                <div class="lg:col-span-3">
                    <p class="text-sm font-semibold text-slate-900">Face match</p>
                    <p class="text-xs text-slate-500">Click a photo to see it full size.</p>
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        @foreach (['nid_front' => 'NID — front', 'selfie' => 'Selfie with NID'] as $key => $label)
                            <figure>
                                @if (! $o->{\App\Models\User::VERIFICATION_DOCUMENTS[$key][0]})
                                    <div class="grid aspect-[4/3] place-items-center rounded-2xl bg-slate-100 text-center text-sm text-slate-400 ring-1 ring-slate-200">
                                        <span><x-icon name="image" class="mx-auto h-8 w-8"/> Not uploaded</span>
                                    </div>
                                @elseif ($o->documentIsImage($key))
                                    <button type="button" @click="zoom = '{{ $doc($key) }}'" class="group block w-full overflow-hidden rounded-2xl bg-slate-900 ring-1 ring-slate-200">
                                        <img src="{{ $doc($key) }}" alt="{{ $label }}" loading="lazy" class="aspect-[4/3] w-full object-contain transition group-hover:scale-[1.03]">
                                    </button>
                                @else
                                    <a href="{{ $doc($key) }}" target="_blank" rel="noopener" class="grid aspect-[4/3] place-items-center rounded-2xl bg-slate-50 text-center text-sm font-semibold text-slate-600 ring-1 ring-slate-200 hover:ring-brand-400">
                                        <span><x-icon name="doc" class="mx-auto h-8 w-8"/> Open PDF ↗</span>
                                    </a>
                                @endif
                                <figcaption class="mt-1.5 text-center text-xs font-medium text-slate-600">{{ $label }}</figcaption>
                            </figure>
                        @endforeach
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        @foreach (['nid_back' => 'NID — back', 'business' => 'Business proof'] as $key => $label)
                            @php $has = (bool) $o->{\App\Models\User::VERIFICATION_DOCUMENTS[$key][0]}; @endphp
                            @if (! $has)
                                <span class="flex items-center gap-2 rounded-xl bg-slate-50 p-2.5 text-xs text-slate-400 ring-1 ring-slate-200"><x-icon name="doc" class="h-4 w-4"/> {{ $label }}: missing</span>
                            @elseif ($o->documentIsImage($key))
                                <button type="button" @click="zoom = '{{ $doc($key) }}'" class="flex items-center gap-3 rounded-xl p-2 text-left text-sm ring-1 ring-slate-200 hover:ring-brand-400">
                                    <img src="{{ $doc($key) }}" alt="" loading="lazy" class="h-12 w-16 shrink-0 rounded-lg bg-slate-100 object-cover"><span class="font-medium">{{ $label }}</span>
                                </button>
                            @else
                                <a href="{{ $doc($key) }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-xl p-2 text-sm ring-1 ring-slate-200 hover:ring-brand-400">
                                    <span class="grid h-12 w-16 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-500"><x-icon name="doc"/></span><span class="font-medium">{{ $label }} (PDF) ↗</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Checklist + decision --}}
                <div class="lg:col-span-2">
                    @if ($o->verificationRejected() && $o->verification_note)
                        <p class="mb-4 rounded-xl bg-rose-50 p-3 text-sm text-rose-800"><b>Rejected:</b> {{ $o->verification_note }}</p>
                    @endif

                    @unless ($o->isVerifiedOwner())
                        <p class="text-sm font-semibold text-slate-900">Before approving, confirm:</p>
                        <div class="mt-3 space-y-2">
                            @foreach ($checks as $k => $text)
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl p-2.5 text-sm ring-1 transition"
                                       :class="checked.includes('{{ $k }}') ? 'bg-brand-50 ring-brand-300' : 'ring-slate-200 hover:bg-slate-50'">
                                    <input type="checkbox" value="{{ $k }}" x-model="checked" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600">
                                    <span>{{ $text }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endunless

                    <div class="mt-5 flex flex-wrap gap-2">
                        @unless ($o->isVerifiedOwner())
                            <form method="POST" action="{{ route('admin.owners.approve', $o) }}">@csrf
                                <button class="btn-primary" :disabled="checked.length < {{ count($checks) }}">
                                    <x-icon name="check" class="h-4 w-4"/> Approve owner
                                </button>
                            </form>
                        @endunless
                        @unless ($o->verificationRejected())
                            <button type="button" class="btn-light text-rose-600" @click="rejecting = !rejecting">
                                {{ $o->isVerifiedOwner() ? 'Revoke verification' : 'Reject' }}
                            </button>
                        @endunless
                    </div>
                    @unless ($o->isVerifiedOwner())
                        <p class="mt-2 text-xs text-slate-500" x-show="checked.length < {{ count($checks) }}">Tick all {{ count($checks) }} checks to enable Approve.</p>
                    @endunless

                    <form method="POST" action="{{ route('admin.owners.reject', $o) }}" x-show="rejecting" x-cloak class="mt-4 space-y-2">@csrf
                        <div class="flex flex-wrap gap-1.5">
                            @foreach (['Selfie does not match the NID photo', 'NID photo is blurry — please upload a clearer picture', 'Selfie must show your face and NID clearly', 'Business proof is missing or does not match'] as $r)
                                <button type="button" class="chip !py-1 !text-xs" @click="$refs.reason{{ $o->id }}.value = @js($r)">{{ $r }}</button>
                            @endforeach
                        </div>
                        <input name="reason" x-ref="reason{{ $o->id }}" class="input" required maxlength="500" placeholder="Reason shown to the owner">
                        <button class="btn-danger btn-sm">Confirm {{ $o->isVerifiedOwner() ? 'revoke' : 'rejection' }}</button>
                    </form>
                </div>
            </div>

            {{-- Full-size viewer --}}
            <div x-show="zoom" x-cloak x-transition.opacity @click="zoom = null" @keydown.escape.window="zoom = null"
                 class="fixed inset-0 z-50 grid place-items-center bg-black/90 p-4">
                <img :src="zoom" alt="" class="max-h-[90vh] max-w-full rounded-xl object-contain">
                <button class="absolute right-4 top-4 grid h-10 w-10 place-items-center rounded-full bg-white/10 text-white" aria-label="Close"><x-icon name="x"/></button>
            </div>
        </div>
    @empty
        <div class="card p-10 text-center">
            <span class="icon-tile mx-auto bg-brand-50 text-brand-600"><x-icon name="check"/></span>
            <p class="mt-3 text-sm text-slate-500">No owners here.</p>
        </div>
    @endforelse
</div>
<div class="mt-6">{{ $owners->links() }}</div>
@endsection
