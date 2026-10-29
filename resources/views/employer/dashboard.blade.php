@extends('layouts.employer')
@section('title', 'Employer dashboard')
@section('content')
    <x-page-header :title="$employer->company_name" subtitle="Your hiring at a glance.">
        <a href="{{ route('employer.jobs.create') }}" class="btn-gold no-underline"><x-icon name="plus" class="h-5 w-5"/> Post a job</a>
    </x-page-header>

    @include('partials.recent-updates')
    @unless ($employer->isVerified())
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Your company is not verified yet. Jobs can still be posted. <a href="{{ route('employer.company.edit') }}" class="font-semibold">Complete your company details</a> to speed up verification.
        </div>
    @endunless

    <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
        <x-stat label="Open jobs" :value="$stats['open_jobs']" :href="route('employer.jobs.index')" />
        <x-stat label="Applicants" :value="$stats['applicants']" />
        <x-stat label="To review" :value="$stats['to_review']" tone="gold" />
        <x-stat label="Interviews" :value="$stats['interviews']" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section aria-labelledby="recent">
            <h2 id="recent" class="mb-2 text-lg font-bold">Latest applicants</h2>
            @if ($recent->isEmpty())
                <x-empty title="No applicants yet" icon="users">Publish a job and matching candidates will be notified.</x-empty>
            @else
                <ul class="card-flat divide-y divide-ink-100 overflow-hidden">
                    @foreach ($recent as $a)
                        <li>
                            <a href="{{ route('employer.jobs.applicants', $a->job_posting_id) }}" class="flex items-center gap-3 px-4 py-3 no-underline hover:bg-ink-50">
                                <x-match-ring :score="$a->match_score" size="40" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-ink-900">{{ $a->candidate->name }}</span>
                                    <span class="block truncate text-xs text-ink-500">{{ $a->job->title }} · {{ $a->created_at->diffForHumans() }}</span>
                                </span>
                                <x-status-badge :status="$a->status" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section aria-labelledby="jobs">
            <div class="mb-2 flex items-end justify-between"><h2 id="jobs" class="text-lg font-bold">Your jobs</h2><a href="{{ route('employer.jobs.index') }}" class="text-sm font-semibold">Manage</a></div>
            @if ($jobs->isEmpty())
                <x-empty title="No jobs yet" icon="briefcase">Post your first job in two minutes.<div class="mt-3"><a href="{{ route('employer.jobs.create') }}" class="btn-primary btn-sm no-underline">Post a job</a></div></x-empty>
            @else
                <ul class="card-flat divide-y divide-ink-100 overflow-hidden">
                    @foreach ($jobs as $job)
                        <li class="flex items-center gap-3 px-4 py-3">
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-ink-900">{{ $job->title }}</span>
                                <span class="block text-xs text-ink-500">{{ $job->applications_count }} {{ \Illuminate\Support\Str::plural('applicant', $job->applications_count) }}</span>
                            </span>
                            <x-badge :color="$job->status === 'open' ? 'green' : ($job->status === 'draft' ? 'amber' : 'slate')">{{ ucfirst($job->status) }}</x-badge>
                            <a href="{{ route('employer.jobs.applicants', $job) }}" class="btn-ghost btn-sm no-underline">View</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <section class="card mt-6 flex items-center gap-4">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-green-100 text-green-700"><x-icon name="inbox"/></span>
        <div class="min-w-0 flex-1"><p class="font-semibold text-ink-900">Need help finding someone?</p><p class="text-sm text-ink-600">Send a hiring request and our team will shortlist for you.</p></div>
        <a href="{{ route('employer.hiring.create') }}" class="btn-ghost btn-sm no-underline">Request</a>
    </section>
@endsection
