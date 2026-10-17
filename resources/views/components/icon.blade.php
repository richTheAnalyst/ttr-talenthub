@props(['name', 'class' => 'h-6 w-6'])
@php
    $paths = [
        'home' => 'M3 10.5 12 3l9 7.5M5 9.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1V9.5',
        'briefcase' => 'M4 8h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Zm5 0V6a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2M3 13h18',
        'bell' => 'M6 9a6 6 0 1 1 12 0c0 5 2 6 2 6H4s2-1 2-6Zm4 9a2 2 0 0 0 4 0',
        'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0',
        'users' => 'M9 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm-6.5 9a6.5 6.5 0 0 1 13 0M16 4.3a3.5 3.5 0 0 1 0 6.4M18 14.2a6.5 6.5 0 0 1 3.5 5.8',
        'doc' => 'M7 3h7l5 5v12a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm7 0v5h5M9 13h6M9 17h6',
        'search' => 'm21 21-4.3-4.3M10.5 17a6.5 6.5 0 1 0 0-13 6.5 6.5 0 0 0 0 13Z',
        'plane' => 'M3 12.5 21 4l-6 16-3-7-9-.5Zm9 .5 4-4',
        'building' => 'M4 21V5a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v16M14 10h5a1 1 0 0 1 1 1v10M3 21h18M8 8h2M8 12h2M8 16h2',
        'chart' => 'M4 20V10m6 10V4m6 16v-7m5 7H3',
        'inbox' => 'M4 13h4l1.5 3h5L16 13h4M4 13l2-8h12l2 8v6a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-6Z',
        'plus' => 'M12 5v14M5 12h14',
        'logout' => 'M15 4h3a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-3M10 16l-4-4 4-4M6 12h10',
        'check' => 'm5 12.5 4.5 4.5L19 7.5',
        'chevron' => 'm9 6 6 6-6 6',
        'upload' => 'M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 16v3a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-3',
        'pin' => 'M12 21s7-5.7 7-11a7 7 0 1 0-14 0c0 5.3 7 11 7 11Zm0-8.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z',
        'clock' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-14v5l3 2',
        'shield' => 'M12 3 5 6v5c0 4.6 3 8.4 7 10 4-1.6 7-5.4 7-10V6l-7-3Zm-3 9 2.2 2.2L15.5 10',
        'spark' => 'M12 3v4m0 10v4M3 12h4m10 0h4M6.3 6.3l2.8 2.8m5.8 5.8 2.8 2.8m0-11.4-2.8 2.8m-5.8 5.8-2.8 2.8',
    ];
    $d = $paths[$name] ?? $paths['spark'];
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes->merge(['class' => $class]) }}><path d="{{ $d }}"/></svg>
