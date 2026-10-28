@extends('layouts.admin')
@section('title', 'Talent search')
@section('content')
@php $f = $filters; @endphp
    <x-page-header title="Talent search" subtitle="Find candidates by skill, location and experience. Only candidates who allow search appear ({{ number_format($total) }} right now)." />

    <form method="GET" action="{{ route('admin.talent.index') }}" class="card mb-5 space-y-4" role="search">
        <div>
            <label for="skills" class="label">Skills</label>
            <input id="skills" name="skills" value="{{ $f['skills'] }}" class="input" placeholder="e.g. accounting, excel, sales (separate with commas)" autocomplete="off">
            @if ($popularSkills->isNotEmpty())
                <p class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($popularSkills as $name)
                        <button type="button" class="chip hover:bg-cobalt-100"
                                onclick="var i=document.getElementById('skills');i.value=(i.value.trim()?i.value.trim().replace(/,$/,'')+', ':'')+@js($name);i.focus()">+ {{ $name }}</button>
                    @endforeach
                </p>
            @endif
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div><label for="location" class="label">Location</label><input id="location" name="location" value="{{ $f['location'] }}" list="locations" autocomplete="off" class="input" placeholder="e.g. Kumasi"></div>
            <div><label for="years" class="label">Min. years experience</label><input id="years" name="years" type="number" min="0" max="50" value="{{ $f['years'] }}" class="input" placeholder="0"></div>
            <div>
                <label for="education" class="label">Education</label>
                <select id="education" name="education" class="input">
                    <option value="">Any</option>
                    @foreach ($education as $k => $l)<option value="{{ $k }}" @selected($f['education'] === $k)>{{ $l }}</option>@endforeach
                </select>
            </div>
            <div>
                <label for="mode" class="label">Skills match</label>
                <select id="mode" name="mode" class="input">
                    <option value="any" @selected($f['mode'] === 'any')>Any of the skills</option>
                    <option value="all" @selected($f['mode'] === 'all')>All of the skills</option>
                </select>
            </div>
        </div>

        <div><label for="q" class="label">Name, email or job title</label><input id="q" name="q" value="{{ $f['q'] }}" class="input" placeholder="Optional"></div>

        <div class="flex items-center gap-2">
            <button class="btn-primary" type="submit"><x-icon name="search" class="h-5 w-5"/> Search talent</button>
            @if ($searched)<a href="{{ route('admin.talent.index') }}" class="btn-ghost no-underline">Clear</a>@endif
        </div>
    </form>
    <x-location-list />

    @if ($profiles->isEmpty())
        <x-empty title="No candidates found" icon="users">{{ $searched ? 'Try fewer skills, "Any of the skills", or a wider location.' : 'Candidates appear here once they create a profile.' }}</x-empty>
    @else
        <ul class="space-y-3">
            @foreach ($profiles as $p)
                @php
                    $cv = $p->user->cvs->sortByDesc('is_primary')->first();
                    $have = $p->skills->pluck('name');
                    $matched = $have->filter(fn ($n) => in_array(mb_strtolower($n), $skillTokens, true));
                @endphp
                <li class="card">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-ink-900">{{ $p->user->name }}
                                @if ($skillTokens)<x-badge color="green">{{ $matched->count() }}/{{ count($skillTokens) }} skills</x-badge>@endif
                            </p>
                            <p class="text-sm text-ink-600">{{ $p->headline ?: ($p->desired_title ?: 'No headline yet') }}</p>
                            <p class="mt-1 flex flex-wrap gap-x-3 text-xs text-ink-500">
                                @if ($p->location)<span>{{ $p->location }}</span>@endif
                                <span>{{ $p->years_experience }} yr{{ $p->years_experience === 1 ? '' : 's' }} experience</span>
                                @if ($p->education_level)<span>{{ $education[$p->education_level] ?? $p->education_level }}</span>@endif
                                <span>{{ $p->user->email }}</span>
                            </p>
                        </div>
                        <div class="flex gap-2">
                            @if ($cv)<a href="{{ route('cv.download', $cv) }}" class="btn-primary btn-sm no-underline">Download CV</a>@else<span class="chip">No CV</span>@endif
                            <a href="{{ route('admin.cvs.index', ['q' => $p->user->email]) }}" class="btn-ghost btn-sm no-underline">CV notes</a>
                        </div>
                    </div>
                    @if ($have->isNotEmpty())
                        <p class="mt-3 flex flex-wrap gap-1.5">
                            @foreach ($have->take(12) as $name)
                                <span class="chip {{ $matched->contains($name) ? 'chip-ok' : '' }}">{{ $name }}</span>
                            @endforeach
                        </p>
                    @endif
                </li>
            @endforeach
        </ul>
        @include('partials.pager', ['p' => $profiles])
    @endif
@endsection