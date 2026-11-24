<button type="button" x-data="{ busy: false, err: '' }"
        @click="
            if (!navigator.geolocation) { err = 'Location not supported'; return; }
            busy = true;
            navigator.geolocation.getCurrentPosition(
                p => window.location = '{{ route('turfs.index') }}?lat=' + p.coords.latitude.toFixed(5) + '&lng=' + p.coords.longitude.toFixed(5) + '&radius=15',
                () => { busy = false; err = 'Please allow location access, or search by area.'; },
                { enableHighAccuracy: true, timeout: 10000 }
            )"
        class="{{ $class ?? 'btn-light' }}" :disabled="busy">
    <span x-show="!busy">📍 Turfs near me</span>
    <span x-show="busy" x-cloak>Finding your location…</span>
    <span x-show="err" x-text="err" x-cloak class="block text-xs font-normal text-rose-600"></span>
</button>
