{{-- Owner verification document fields. Used on owner sign-up and on the panel's verification page. --}}
<div class="space-y-5 rounded-2xl bg-slate-50 p-5 ring-1 ring-slate-200">
    <div class="flex gap-3">
        <span class="icon-tile !h-10 !w-10 bg-white text-brand-700 ring-1 ring-slate-200"><x-icon name="shield"/></span>
        <p class="text-sm text-slate-600">
            <b class="block text-slate-900">Owner verification</b>
            To protect players from fake listings, our team checks every owner. Your documents are stored privately and only the admin can see them.
        </p>
    </div>

    <div><label class="label">NID number</label>
        <input name="nid_number" value="{{ old('nid_number', $nid ?? '') }}" class="input" inputmode="numeric" placeholder="10, 13 or 17 digits" required></div>

    {{-- NID + business proof --}}
    <div class="grid gap-3 sm:grid-cols-3">
        @foreach ([['nid_front', 'NID — front side'], ['nid_back', 'NID — back side'], ['business_document', 'Trade license / business proof']] as [$field, $label])
            <label x-data="{ name: '' }" class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed bg-white p-4 text-center transition hover:border-brand-400"
                   :class="name ? 'border-brand-500 bg-brand-50' : 'border-slate-300'">
                <span class="grid h-10 w-10 place-items-center rounded-full" :class="name ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-500'">
                    <x-icon name="doc" class="h-5 w-5" x-show="!name"/><x-icon name="check" class="h-5 w-5" x-show="name" x-cloak/>
                </span>
                <span class="text-sm font-semibold text-slate-800">{{ $label }}</span>
                <span class="line-clamp-1 text-xs" :class="name ? 'text-brand-700' : 'text-slate-400'" x-text="name || 'Tap to upload'">Tap to upload</span>
                <input type="file" name="{{ $field }}" accept="image/*,application/pdf" class="sr-only" required @change="name = $event.target.files[0]?.name || ''">
            </label>
        @endforeach
    </div>

    {{-- Selfie --}}
    <div x-data="{ preview: null }" class="grid items-center gap-4 rounded-2xl bg-white p-4 ring-1 ring-slate-200 sm:grid-cols-[9rem_1fr]">
        <label class="relative block aspect-[3/4] cursor-pointer overflow-hidden rounded-xl border-2 border-dashed transition hover:border-brand-400"
               :class="preview ? 'border-brand-500' : 'border-slate-300 bg-slate-50'">
            <img x-show="preview" x-cloak :src="preview" alt="Selfie preview" class="h-full w-full object-cover">
            {{-- Example pose: face + card held next to it --}}
            <svg x-show="!preview" viewBox="0 0 120 160" class="h-full w-full text-slate-300" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                <circle cx="52" cy="58" r="22"/><path d="M14 150c3-30 18-46 38-46s35 16 38 46"/>
                <rect x="70" y="84" width="40" height="27" rx="4" fill="#fff" stroke="#10b981"/><circle cx="81" cy="96" r="5" stroke="#10b981"/><path d="M90 93h14M90 101h10" stroke="#10b981"/>
            </svg>
            <span x-show="!preview" class="absolute inset-x-0 bottom-2 text-center text-xs font-semibold text-slate-500">Tap to take</span>
            <input type="file" name="selfie" accept="image/*" capture="user" class="sr-only" required
                   @change="const f = $event.target.files[0]; preview = f ? URL.createObjectURL(f) : null">
        </label>
        <div class="text-sm">
            <p class="flex items-center gap-2 font-semibold text-slate-900"><x-icon name="user" class="h-4 w-4 text-brand-600"/> Selfie holding your NID</p>
            <p class="mt-1 text-slate-600">Take a clear photo of your face while holding the <b>front of your NID</b> next to it. The admin compares it with your NID photo.</p>
            <ul class="mt-3 space-y-1.5 text-xs text-slate-500">
                <li class="flex gap-2"><x-icon name="check" class="h-4 w-4 shrink-0 text-brand-600"/> Face fully visible — no sunglasses, mask or cap</li>
                <li class="flex gap-2"><x-icon name="check" class="h-4 w-4 shrink-0 text-brand-600"/> Good light, not blurry</li>
                <li class="flex gap-2"><x-icon name="check" class="h-4 w-4 shrink-0 text-brand-600"/> NID photo side facing the camera</li>
            </ul>
            <p x-show="preview" x-cloak class="mt-3 text-xs font-semibold text-brand-700">✓ Selfie added — tap the photo to retake.</p>
        </div>
    </div>

    <p class="text-xs text-slate-500">NID and business proof: photo (JPG, PNG) or PDF, up to 5 MB each. Selfie: photo only. For business proof, a trade license, land/lease agreement or a utility bill in the business name all work.</p>
</div>
