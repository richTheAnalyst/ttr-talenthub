@extends('layouts.app')
@section('title', 'Saved jobs')
@section('content')
    <x-page-header title="Saved jobs" subtitle="Jobs you bookmarked to come back to." />

    @if ($jobs->isEmpty())
        <x-empty title="No saved jobs yet" icon="briefcase">Tap "Save job" on any job to keep it here.</x-empty>
        <p class="mt-4 text-center"><a href="{{ route('jobs.index') }}" class="btn-primary no-underline">Browse jobs</a></p>
    @else
        <div class="grid gap-3 md:grid-cols-2">
            @foreach ($jobs as $job)
                <x-job-card :job="$job" />
            @endforeach
        </div>
    @endif
@endsection