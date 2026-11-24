@extends('layouts.panel')
@section('title', 'Website content')
@section('subtitle', 'Change the texts and pictures on the public website. Changes go live as soon as you save.')
@section('actions')<a href="{{ route('home') }}" target="_blank" class="btn-light btn-sm"><x-icon name="external" class="h-4 w-4"/> View website</a>@endsection
@section('content')
@php
    $sections = \App\Support\SiteContent::SECTIONS;
    [$title, $icon, $help, $fields] = $sections[$active];
    $name = fn ($key) => \App\Http\Controllers\Admin\ContentController::input($key);
@endphp

<div class="grid gap-6 lg:grid-cols-4">
    {{-- Section tabs --}}
    <nav class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 lg:mx-0 lg:flex-col lg:gap-1 lg:overflow-visible lg:px-0">
        @foreach ($sections as $key => [$t, $i])
            <a href="{{ route('admin.content', ['tab' => $key]) }}"
               class="flex shrink-0 items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition {{ $active === $key ? 'bg-white text-brand-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:bg-white/70' }}">
                <x-icon :name="$i" class="h-5 w-5 {{ $active === $key ? 'text-brand-600' : 'text-slate-400' }}"/> {{ $t }}
            </a>
        @endforeach
    </nav>

    {{-- Editor --}}
    <form method="POST" action="{{ route('admin.content.save') }}" enctype="multipart/form-data" class="card lg:col-span-3">
        @csrf
        <input type="hidden" name="section" value="{{ $active }}">
        <div class="card-head">
            <div class="flex items-center gap-3">
                <span class="icon-tile !h-10 !w-10 bg-brand-100 text-brand-700"><x-icon :name="$icon"/></span>
                <div><h2 class="card-title">{{ $title }}</h2><p class="text-xs text-slate-500">{{ $help }}</p></div>
            </div>
        </div>

        <div class="space-y-6 p-5 md:p-6">
            @foreach ($fields as $key => $f)
                @php
                    [$label, $type] = $f;
                    $hint = $f[3] ?? null;
                    $n = $name($key);
                    $val = old($n, \App\Support\SiteContent::stored($key) ?? (is_string($f[2]) ? $f[2] : ''));
                @endphp

                @switch($type)
                    @case('toggle')
                        <label class="flex cursor-pointer items-center justify-between gap-4 rounded-2xl p-4 ring-1 ring-slate-200">
                            <span class="text-sm font-medium text-slate-800">{{ $label }}</span>
                            <input type="hidden" name="{{ $n }}" value="0">
                            <input type="checkbox" name="{{ $n }}" value="1" class="peer sr-only" @checked(\App\Support\SiteContent::on($key))>
                            <span class="relative h-6 w-11 shrink-0 rounded-full bg-slate-300 transition after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:bg-brand-600 peer-checked:after:translate-x-5"></span>
                        </label>
                        @break

                    @case('image')
                        <div x-data="{ preview: @js(\App\Support\SiteContent::image($key)), reset: false, custom: @js(\App\Support\SiteContent::hasCustomImage($key)) }">
                            <p class="label">{{ $label }}</p>
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                                <img x-show="preview" :src="preview" alt="" class="aspect-[16/9] w-full rounded-2xl object-cover ring-1 ring-slate-200 sm:w-72" :class="reset && 'opacity-40'">
                                <div x-show="!preview" class="grid aspect-[16/9] w-full place-items-center rounded-2xl border-2 border-dashed border-slate-300 text-sm text-slate-400 sm:w-72">No picture</div>
                                <div class="space-y-2">
                                    <label class="btn-light btn-sm cursor-pointer"><x-icon name="image" class="h-4 w-4"/> Choose new image
                                        <input type="file" name="{{ $n }}" accept="image/*" class="sr-only" @change="const f = $event.target.files[0]; if (f) { preview = URL.createObjectURL(f); reset = false }">
                                    </label>
                                    <template x-if="custom">
                                        <label class="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" name="{{ $n }}_reset" value="1" x-model="reset" class="rounded border-slate-300"> {{ $f[2] ? 'Use the default picture again' : 'Remove this picture' }}</label>
                                    </template>
                                    <p class="hint">{{ $hint ?? 'JPG or PNG, up to 4 MB.' }}</p>
                                </div>
                            </div>
                        </div>
                        @break

                    @case('faq')
                        <div x-data="{ items: @js(old($n, \App\Support\SiteContent::faqRaw())) }">
                            <p class="label">{{ $label }}</p>
                            <div class="space-y-3">
                                <template x-for="(item, i) in items" :key="i">
                                    <div class="rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-xs font-semibold text-slate-500" x-text="'Question ' + (i + 1)"></p>
                                            <button type="button" @click="items.splice(i, 1)" class="text-xs font-semibold text-rose-600 hover:text-rose-700">Remove</button>
                                        </div>
                                        <input :name="'{{ $n }}[' + i + '][q]'" x-model="item.q" class="input mt-2" placeholder="Question">
                                        <textarea :name="'{{ $n }}[' + i + '][a]'" x-model="item.a" rows="2" class="input mt-2" placeholder="Answer"></textarea>
                                    </div>
                                </template>
                            </div>
                            <button type="button" @click="items.push({ q: '', a: '' })" class="btn-light btn-sm mt-3"><x-icon name="plus" class="h-4 w-4"/> Add question</button>
                        </div>
                        @break

                    @default
                        <div>
                            <label class="label" for="{{ $n }}">{{ $label }}</label>
                            @if (in_array($type, ['textarea', 'lines']))
                                <textarea id="{{ $n }}" name="{{ $n }}" rows="{{ $type === 'lines' ? 5 : 3 }}" class="input">{{ $val }}</textarea>
                            @else
                                <input id="{{ $n }}" name="{{ $n }}" value="{{ $val }}" type="{{ ['email' => 'email', 'date' => 'date'][$type] ?? 'text' }}" class="input {{ $type === 'date' ? 'max-w-xs' : '' }}">
                            @endif
                            @if ($hint)<p class="hint">{{ $hint }}</p>@endif
                        </div>
                @endswitch
            @endforeach

            <p class="rounded-xl bg-sky-50 p-3 text-xs text-sky-900 ring-1 ring-sky-200">
                <b>Tip:</b> you can write <code>{hours}</code> (payment deadline), <code>{cancel_hours}</code>, <code>{turfs}</code> or <code>{areas}</code> in any text — they are replaced with the live numbers.
            </p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 p-5">
            <button type="submit" form="reset-{{ $active }}" class="btn-ghost btn-sm text-slate-500" onclick="return confirm('Put this whole section back to the default texts and pictures?')">Reset section to default</button>
            <button class="btn-primary btn-lg">Save changes</button>
        </div>
    </form>
</div>
<form id="reset-{{ $active }}" method="POST" action="{{ route('admin.content.reset', $active) }}" class="hidden">@csrf</form>
@endsection
