@props(['label', 'value', 'href' => null, 'tone' => 'default'])
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'card block '.($href ? 'transition hover:border-cobalt-300' : '')]) }}>
    <p class="text-xs font-medium uppercase tracking-wide text-ink-500">{{ $label }}</p>
    <p class="mt-1 font-display text-3xl font-bold {{ $tone === 'gold' ? 'text-gold-600' : 'text-ink-900' }}">{{ $value }}</p>
</{{ $tag }}>
