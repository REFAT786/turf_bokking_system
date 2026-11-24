@props(['name', 'class' => 'h-5 w-5'])
@php
    // Simple 24×24 stroke icons (drawn for this project).
    $paths = [
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h5v-6h4v6h5V9.5"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
        'list' => '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/>',
        'pitch' => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M12 5v14"/><circle cx="12" cy="12" r="2.6"/><path d="M2.5 9.5h2.5v5H2.5M21.5 9.5H19v5h2.5"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.6 3.2-5.5 6.5-5.5s5.9 1.9 6.5 5.5"/><path d="M16 4.8a3.3 3.3 0 0 1 0 6.4M18 14.8c2 .7 3.2 2.4 3.5 5.2"/>',
        'chart' => '<path d="M4 20V4M4 20h16"/><path d="M8 16v-4M12 16V8M16 16v-6"/>',
        'shield' => '<path d="M12 3 4.5 6v5.5c0 4.6 3.1 8.3 7.5 9.5 4.4-1.2 7.5-4.9 7.5-9.5V6z"/><path d="m8.8 12 2.2 2.2 4.2-4.4"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5.3 5.3l2.1 2.1M16.6 16.6l2.1 2.1M5.3 18.7l2.1-2.1M16.6 7.4l2.1-2.1"/>',
        'logout' => '<path d="M14 4h4.5A1.5 1.5 0 0 1 20 5.5v13a1.5 1.5 0 0 1-1.5 1.5H14"/><path d="M10 8l-4 4 4 4M6 12h10"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m20 20-4.2-4.2"/>',
        'pin' => '<path d="M12 21s-7-6.1-7-11.5a7 7 0 0 1 14 0C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'phone' => '<path d="M5 4h3.5l1.5 4.5-2.2 1.4a11 11 0 0 0 6.3 6.3l1.4-2.2L20 15.5V19a1.5 1.5 0 0 1-1.6 1.5A16 16 0 0 1 3.5 5.6 1.5 1.5 0 0 1 5 4z"/>',
        'wallet' => '<path d="M4 7.5A2.5 2.5 0 0 1 6.5 5H18v3"/><rect x="4" y="8" width="16.5" height="11.5" rx="2.5"/><circle cx="16.3" cy="13.8" r="1.2"/>',
        'star' => '<path d="m12 3.5 2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'external' => '<path d="M14 4h6v6M20 4l-8.5 8.5"/><path d="M18 14v4.5a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 4 18.5v-11A1.5 1.5 0 0 1 5.5 6H10"/>',
        'image' => '<rect x="3.5" y="4.5" width="17" height="15" rx="2.5"/><circle cx="9" cy="9.5" r="1.8"/><path d="m4 17 5-4.5 3.5 3 3-2.5 4.5 4"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4.5 20.5c.8-4 3.8-6 7.5-6s6.7 2 7.5 6"/>',
        'info' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5.5M12 7.6v.2"/>',
        'bolt' => '<path d="M13 3 5 13.5h6L10 21l8-10.5h-6z"/>',
        'moon' => '<path d="M19.5 14.5A8 8 0 0 1 9.5 4.5a8 8 0 1 0 10 10z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M5.3 18.7l1.4-1.4M17.3 6.7l1.4-1.4"/>',
        'doc' => '<path d="M7 3.5h7l4.5 4.5v12a.5.5 0 0 1-.5.5H7A1.5 1.5 0 0 1 5.5 19V5A1.5 1.5 0 0 1 7 3.5z"/><path d="M13.5 3.5V8.5h5M9 13h6M9 16.5h4"/>',
        'whatsapp' => '<path d="M4 20l1.2-4A8.5 8.5 0 1 1 8.4 19z"/><path d="M9 8.5c0 3.5 2.6 6.5 6.5 6.5l1-1.6-2-1-1 .9c-1.3-.5-2.3-1.5-2.8-2.8l.9-1-1-2z"/>',
        'eye' => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z"/><circle cx="12" cy="12" r="3"/>',
        'trash' => '<path d="M4.5 7h15M10 7V4.5h4V7M6.5 7l1 13h9l1-13M10 11v5.5M14 11v5.5"/>',
    ];
@endphp
<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? $paths['info'] !!}</svg>
