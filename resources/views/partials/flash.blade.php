@if (session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
         class="mb-4 flex items-start gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">
        <span class="font-bold">✓</span><span class="flex-1">{{ session('success') }}</span>
        <button @click="show=false" class="text-emerald-600">✕</button>
    </div>
@endif
@if ($errors->any())
    <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 ring-1 ring-rose-200">
        <ul class="list-inside list-disc space-y-0.5">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif
