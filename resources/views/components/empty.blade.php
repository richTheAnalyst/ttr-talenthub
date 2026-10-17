@props(['title', 'icon' => 'inbox'])
<div {{ $attributes->merge(['class' => 'card-flat flex flex-col items-center px-6 py-10 text-center']) }}>
    <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-cobalt-50 text-cobalt-600"><x-icon :name="$icon"/></span>
    <p class="font-display text-lg font-semibold text-ink-900">{{ $title }}</p>
    <div class="mt-1 max-w-sm text-sm text-ink-500">{{ $slot }}</div>
</div>
