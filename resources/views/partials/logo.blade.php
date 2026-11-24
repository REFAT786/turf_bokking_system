<a href="{{ route('home') }}" class="flex items-center gap-2.5 font-bold {{ ($dark ?? false) ? 'text-white' : 'text-slate-900' }}">
    <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-white shadow-md shadow-brand-600/30">
        <svg viewBox="0 0 24 24" class="h-5 w-5" aria-hidden="true">
            <polygon points="12.0,3.6 14.3,9.4 20.6,9.8 15.7,13.8 17.3,19.9 12.0,16.5 6.7,19.9 8.3,13.8 3.4,9.8 9.7,9.4" fill="currentColor" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/>
        </svg>
    </span>
    <span class="text-lg tracking-tight">{{ site('brand.name_1') }}<span class="{{ ($dark ?? false) ? 'text-brand-400' : 'text-brand-600' }}">{{ site('brand.name_2') }}</span></span>
</a>
