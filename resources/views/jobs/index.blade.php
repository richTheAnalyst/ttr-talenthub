@extends('layouts.app')
@section('title', 'Browse jobs')

@section('content')
    <x-page-header title="Browse jobs" subtitle="Open roles from verified and new employers." />

    <form method="GET" action="{{ route('jobs.index') }}" class="card mb-5 grid gap-3 sm:grid-cols-[1fr_1fr_12rem_auto]" role="search">
        <div>
            <label for="q" class="label">Keyword</label>
            <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Title, skill or company" class="input">
        </div>
        <div>
            <label for="location" class="label">Location</label>
            <input id="location" name="location" type="text" value="{{ $filters['location'] ?? '' }}" placeholder="e.g. Accra" class="input">
        </div>
        <x-select name="type" label="Type" :options="\App\Enums\JobType::options()" :value="$filters['type'] ?? null" placeholder="Any type" />
        <div class="flex items-end gap-2">
            <button class="btn-primary flex-1 sm:flex-none" type="submit">Search</button>
            @if (array_filter($filters))<a href="{{ route('jobs.index') }}" class="btn-ghost no-underline">Clear</a>@endif
        </div>
    </form>

    @if ($jobs->isEmpty())
        <x-empty title="No jobs match your search" icon="search">Try fewer words or a different location. You can also create a job alert and we will tell you when a match is posted.</x-empty>
    @else
        <div class="grid gap-3 md:grid-cols-2">
            @foreach ($jobs as $job)
                <x-job-card :job="$job" :score="$scores[$job->id] ?? null" />
            @endforeach
        </div>
        @include('partials.pager', ['p' => $jobs])
    @endif
@endsection
