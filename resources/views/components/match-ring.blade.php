{{-- Circular match score. <x-match-ring :score="72" size="48" /> --}}
@props(['score' => 0, 'size' => 48])
@php
    $score = max(0, min(100, (int) $score));
    $r = 18; $c = 2 * M_PI * $r;
    $tone = $score >= 70 ? '#16a34a' : ($score >= 45 ? '#2347d6' : '#8d9bbb');
@endphp
<span class="relative inline-flex shrink-0 items-center justify-center" style="width:{{ $size }}px;height:{{ $size }}px" role="img" aria-label="{{ $score }} percent match">
    <svg viewBox="0 0 44 44" class="h-full w-full -rotate-90" aria-hidden="true">
        <circle cx="22" cy="22" r="{{ $r }}" fill="none" stroke="#e9eef6" stroke-width="4"/>
        <circle cx="22" cy="22" r="{{ $r }}" fill="none" stroke="{{ $tone }}" stroke-width="4" stroke-linecap="round"
                stroke-dasharray="{{ round($c * $score / 100, 1) }} {{ round($c, 1) }}"/>
    </svg>
    <span class="absolute text-[11px] font-bold text-ink-900">{{ $score }}%</span>
</span>
