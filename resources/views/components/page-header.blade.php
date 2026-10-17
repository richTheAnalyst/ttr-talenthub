@props(['title', 'subtitle' => null])
<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div class="min-w-0">
        <h1 class="h-page">{{ $title }}</h1>
        @if ($subtitle)<p class="muted mt-1">{{ $subtitle }}</p>@endif
    </div>
    @if (trim((string) $slot) !== '')<div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>@endif
</div>
