@props(['text' => 'TalentHub', 'tone' => 'light'])
@php
    $file = collect(['logo.svg', 'logo.png', 'logo.webp'])->first(fn ($f) => is_file(public_path('images/'.$f)));
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    @if ($file)
        <span class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden p-1 shadow-sm">
            <img src="{{ asset('images/'.$file) }}?v={{ filemtime(public_path('images/'.$file)) }}" alt="" class="h-full w-full object-contain">
        </span>
    @else
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-coral-500 font-display text-lg font-bold text-white">T</span>
    @endif
    @if ($text)<span class="font-display text-lg font-bold tracking-tight {{ $tone === 'dark' ? 'text-ink-900' : 'text-white' }}">{{ $text }}</span>@endif
</span>