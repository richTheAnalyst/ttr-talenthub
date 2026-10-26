@extends('layouts.admin')
@section('title', 'CVs')
@section('content')
@php
    $f = $filters;
    $active = collect([$f['q'], $f['type'], $f['range'], $f['review'], $f['primary'] ? '1' : '', $f['used'] ? '1' : ''])->filter()->count() + ($f['sort'] !== 'newest' ? 1 : 0);
    $ext = fn ($name) => strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $extStyle = ['pdf' => 'bg-red-50 text-red-600', 'doc' => 'bg-cobalt-50 text-cobalt-600', 'docx' => 'bg-cobalt-50 text-cobalt-600'];
    $avatar = ['bg-cobalt-100 text-cobalt-700', 'bg-gold-100 text-gold-600', 'bg-green-100 text-green-700', 'bg-purple-100 text-purple-700', 'bg-pink-100 text-pink-700'];
    $tabs = ['' => 'All'] + $types;
@endphp

    <x-page-header title="CVs" subtitle="Browse, filter and download candidate CVs." />

    {{-- Summary tiles --}}
    <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <x-stat label="Total CVs" :value="number_format($summary['total'])" />
        <x-stat label="Primary CVs" :value="number_format($summary['primaries'])" tone="gold" />
        <x-stat label="Added this week" :value="number_format($summary['week'])" />
        <x-stat label="Storage used" :value="$summary['mb'].' MB'" />
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.cvs.index') }}" class="card mb-5 space-y-4" role="search">
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-400"/>
                <input name="q" value="{{ $f['q'] }}" type="search" class="input pl-10" placeholder="Search candidate, email or file name" aria-label="Search CVs">
            </div>
            <button class="btn-primary" type="submit">Search</button>
        </div>

        {{-- File type pills --}}
        <div class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1" role="group" aria-label="File type">
            @foreach ($tabs as $key => $label)
                @php
                    $on = $f['type'] === (string) $key;
                    $url = route('admin.cvs.index', array_filter(array_merge(request()->except('page', 'type'), ['type' => $key]), fn ($v) => $v !== '' && $v !== null));
                @endphp
                <a href="{{ $url }}" @if ($on) aria-current="true" @endif
                   class="shrink-0 rounded-full border px-4 py-1.5 text-sm font-medium no-underline transition {{ $on ? 'border-ink-900 bg-ink-900 text-white' : 'border-ink-200 bg-white text-ink-700 hover:border-ink-400' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="range" class="label">Uploaded</label>
                <select id="range" name="range" class="input" data-autosubmit>
                    <option value="">Any time</option>
                    @foreach ($ranges as $k => $l)<option value="{{ $k }}" @selected($f['range'] === (string) $k)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="review" class="label">Review status</label>
                <select id="review" name="review" class="input" data-autosubmit>
                    <option value="">Any status</option>
                    @foreach ($reviewStatuses as $k => $l)<option value="{{ $k }}" @selected($f['review'] === $k)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="sort" class="label">Sort by</label>
                <select id="sort" name="sort" class="input" data-autosubmit>
                    @foreach ($sorts as $k => $l)<option value="{{ $k }}" @selected($f['sort'] === $k)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <label class="flex min-h-[44px] items-center gap-3 self-end rounded-xl border border-ink-200 px-3">
                <input type="checkbox" name="primary" value="1" class="check mt-0" @checked($f['primary']) data-autosubmit>
                <span class="text-sm text-ink-800">Primary CVs only</span>
            </label>
            <label class="flex min-h-[44px] items-center gap-3 self-end rounded-xl border border-ink-200 px-3">
                <input type="checkbox" name="used" value="1" class="check mt-0" @checked($f['used']) data-autosubmit>
                <span class="text-sm text-ink-800">Used in applications</span>
            </label>
        </div>
        <input type="hidden" name="type" value="{{ $f['type'] }}">

        <div class="flex items-center justify-between text-sm">
            <span class="text-ink-600">{{ $active ? $active.' filter'.($active > 1 ? 's' : '').' active' : 'Showing all CVs' }}</span>
            @if ($active)<a href="{{ route('admin.cvs.index') }}" class="font-semibold">Clear all</a>@endif
        </div>
    </form>

    @if ($cvs->isEmpty())
        <x-empty title="No CVs match" icon="doc">{{ $active ? 'Try removing a filter or searching for something else.' : 'CVs appear here once candidates upload them.' }}</x-empty>
    @else
        <ul class="grid gap-3 md:grid-cols-2">
            @foreach ($cvs as $cv)
                @php
                    $e = $ext($cv->original_name);
                    $initial = mb_strtoupper(mb_substr($cv->user->name, 0, 1));
                @endphp
                <li class="card group flex flex-col gap-3 transition hover:-translate-y-0.5 hover:border-cobalt-300 hover:shadow-md">
                    <div class="flex items-start gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full font-display text-lg font-bold {{ $avatar[$cv->user_id % count($avatar)] }}">{{ $initial }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-ink-900">{{ $cv->user->name }}@if ($cv->is_primary) <x-badge color="gold">Primary</x-badge>@endif @if ($cv->review_status) <x-badge :color="['shortlisted' => 'green', 'contacted' => 'blue', 'not_suitable' => 'red'][$cv->review_status] ?? 'blue'">{{ $reviewStatuses[$cv->review_status] ?? $cv->review_status }}</x-badge>@endif</p>
                            <p class="truncate text-xs text-ink-500">{{ $cv->user->email }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 rounded-xl bg-ink-50 p-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-[11px] font-bold uppercase {{ $extStyle[$e] ?? 'bg-ink-100 text-ink-600' }}">{{ $e ?: 'file' }}</span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-ink-900">{{ $cv->original_name }}</p>
                            <p class="text-xs text-ink-500">{{ $cv->humanSize() }} · {{ $cv->created_at->format('j M Y') }} · {{ $cv->created_at->diffForHumans() }}</p>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('cv.download', $cv) }}" class="btn-primary btn-sm flex-1 justify-center no-underline"><x-icon name="upload" class="h-4 w-4 rotate-180"/> Download</a>
                        <a href="{{ route('admin.cvs.index', ['q' => $cv->user->email]) }}" class="btn-ghost btn-sm no-underline">All from this person</a>
                    </div>

                    <details class="rounded-xl border border-ink-200 px-3 py-2" @if ($cv->admin_notes) open @endif>
                        <summary class="cursor-pointer text-sm font-medium text-ink-700">Review &amp; notes</summary>
                        <form method="POST" action="{{ route('admin.cvs.review', $cv) }}" class="mt-3 space-y-3">
                            @csrf @method('PATCH')
                            <select name="review_status" class="input" aria-label="Review status">
                                <option value="">No status</option>
                                @foreach ($reviewStatuses as $k => $l)<option value="{{ $k }}" @selected($cv->review_status === $k)>{{ $l }}</option>@endforeach
                            </select>
                            <textarea name="admin_notes" rows="3" maxlength="2000" class="input" placeholder="Private notes (only admins see these)" aria-label="Private notes">{{ $cv->admin_notes }}</textarea>
                            <button class="btn-primary btn-sm" type="submit">Save</button>
                        </form>
                    </details>
                </li>
            @endforeach
        </ul>
        @include('partials.pager', ['p' => $cvs])
    @endif
@endsection