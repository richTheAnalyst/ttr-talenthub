@extends('layouts.employer')
@section('title', 'My jobs')
@section('content')
    <x-page-header title="My jobs">
        <a href="{{ route('employer.jobs.create') }}" class="btn-gold no-underline"><x-icon name="plus" class="h-5 w-5"/> Post a job</a>
    </x-page-header>

    @if ($jobs->isEmpty())
        <x-empty title="No jobs yet" icon="briefcase">Post a job and we will notify candidates who match.</x-empty>
    @else
        <ul class="space-y-3">
            @foreach ($jobs as $job)
                <li class="card">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-ink-900">{{ $job->title }}</p>
                            <p class="text-sm text-ink-500">{{ $job->job_type->label() }}@if ($job->location) · {{ $job->location }}@endif · {{ $job->applications_count }} {{ \Illuminate\Support\Str::plural('applicant', $job->applications_count) }}</p>
                            @if ($job->expires_at)<p class="text-xs text-ink-400">Closes {{ $job->expires_at->format('j M Y') }}</p>@endif
                        </div>
                        <x-badge :color="$job->status === 'open' ? 'green' : ($job->status === 'draft' ? 'amber' : 'slate')">{{ ucfirst($job->status) }}</x-badge>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('employer.jobs.applicants', $job) }}" class="btn-primary btn-sm no-underline">Applicants</a>
                        <a href="{{ route('employer.jobs.edit', $job) }}" class="btn-ghost btn-sm no-underline">Edit</a>
                        <a href="{{ route('jobs.show', $job) }}" class="btn-ghost btn-sm no-underline">Preview</a>
                        @if ($job->status === 'open')
                            <form method="POST" action="{{ route('employer.jobs.close', $job) }}" data-confirm="Close this job? It will disappear from search.">@csrf<button class="btn-ghost btn-sm" type="submit">Close</button></form>
                        @endif
                        <form method="POST" action="{{ route('employer.jobs.destroy', $job) }}" data-confirm="Delete this job?">@csrf @method('DELETE')<button class="btn-danger btn-sm" type="submit">Delete</button></form>
                    </div>
                </li>
            @endforeach
        </ul>
        @include('partials.pager', ['p' => $jobs])
    @endif
@endsection
