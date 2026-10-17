@extends('layouts.app')
@section('title', $job->title.' at '.$job->employer->company_name)
@section('description', \Illuminate\Support\Str::limit(strip_tags($job->description), 150))

@section('content')
    <a href="{{ route('jobs.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm font-medium"><x-icon name="chevron" class="h-4 w-4 rotate-180"/> All jobs</a>

    <div class="grid gap-5 lg:grid-cols-[1fr_22rem]">
        <article class="space-y-5">
            <header class="card">
                <div class="flex flex-wrap items-center gap-2">
                    <x-badge color="blue">{{ $job->job_type->label() }}</x-badge>
                    @unless ($job->isOpen())<x-badge color="red">Closed</x-badge>@endunless
                    @if ($job->status === 'draft')<x-badge color="amber">Draft</x-badge>@endif
                </div>
                <h1 class="mt-2 text-2xl font-bold sm:text-3xl">{{ $job->title }}</h1>
                <p class="mt-1 flex flex-wrap items-center gap-x-2 text-ink-600">
                    <span class="font-medium">{{ $job->employer->company_name }}</span>
                    @if ($job->employer->isVerified())<span class="inline-flex items-center gap-0.5 text-xs font-medium text-green-700"><x-icon name="shield" class="h-4 w-4"/> Verified employer</span>@endif
                </p>
                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                    <div><dt class="text-xs text-ink-500">Location</dt><dd class="font-medium">{{ $job->location ?: 'Not stated' }}</dd></div>
                    <div><dt class="text-xs text-ink-500">Salary</dt><dd class="font-medium">{{ $job->salaryRange() ?? 'Not stated' }}</dd></div>
                    <div><dt class="text-xs text-ink-500">Experience</dt><dd class="font-medium">{{ $job->experience_min ? $job->experience_min.'+ years' : 'Entry level' }}</dd></div>
                    <div><dt class="text-xs text-ink-500">Closes</dt><dd class="font-medium">{{ $job->expires_at ? $job->expires_at->format('j M Y') : 'Open' }}</dd></div>
                </dl>
            </header>

            <section class="card">
                <h2 class="text-lg font-semibold">About the role</h2>
                <div class="mt-2 whitespace-pre-line text-ink-700">{{ $job->description }}</div>
            </section>

            @if ($job->skills->isNotEmpty())
                <section class="card">
                    <h2 class="text-lg font-semibold">Skills</h2>
                    <p class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($job->skills->sortByDesc(fn ($s) => $s->pivot->is_required) as $skill)
                            <span class="chip {{ $skill->pivot->is_required ? 'bg-cobalt-100 text-cobalt-800' : '' }}">{{ $skill->name }}@unless ($skill->pivot->is_required) <span class="ml-1 text-ink-400">(nice to have)</span>@endunless</span>
                        @endforeach
                    </p>
                </section>
            @endif

            @if ($job->employer->description)
                <section class="card">
                    <h2 class="text-lg font-semibold">About {{ $job->employer->company_name }}</h2>
                    <p class="mt-2 whitespace-pre-line text-ink-700">{{ $job->employer->description }}</p>
                    @if ($job->employer->website)<p class="mt-2 text-sm"><a href="{{ $job->employer->website }}" rel="noopener nofollow" target="_blank">{{ parse_url($job->employer->website, PHP_URL_HOST) ?: $job->employer->website }}</a></p>@endif
                </section>
            @endif
        </article>

        <aside class="space-y-5 lg:sticky lg:top-20 lg:self-start">
            @auth
                @if (auth()->user()->isCandidate())
                    @if ($match)
                        <section class="card">
                            <div class="flex items-center gap-3">
                                <x-match-ring :score="$match['score']" size="64" />
                                <div>
                                    <h2 class="text-base font-semibold">Your match</h2>
                                    <p class="muted">Based on your profile</p>
                                </div>
                            </div>
                            <div class="mt-4">@include('partials.match-breakdown', ['match' => $match])</div>
                        </section>
                    @endif

                    <section class="card" id="apply">
                        @if ($application)
                            <h2 class="text-base font-semibold">You applied</h2>
                            <p class="mt-1 flex items-center gap-2 text-sm text-ink-600"><x-status-badge :status="$application->status"/> {{ $application->created_at->diffForHumans() }}</p>
                            <a href="{{ route('candidate.applications.show', $application) }}" class="btn-primary btn-block mt-3 no-underline">Track application</a>
                        @elseif (! $job->isOpen())
                            <p class="text-sm text-ink-600">This job is no longer accepting applications.</p>
                        @elseif ($cvs->isEmpty())
                            <h2 class="text-base font-semibold">Apply for this job</h2>
                            <p class="mt-1 text-sm text-ink-600">Upload a CV first. You only need to do this once.</p>
                            <a href="{{ route('candidate.cvs.index') }}" class="btn-primary btn-block mt-3 no-underline"><x-icon name="upload" class="h-5 w-5"/> Upload your CV</a>
                        @else
                            <h2 class="text-base font-semibold">Apply for this job</h2>
                            <form method="POST" action="{{ route('candidate.jobs.apply', $job) }}" class="mt-3 space-y-3">
                                @csrf
                                <x-select name="cv_id" label="CV to send" :options="$cvs->mapWithKeys(fn ($cv) => [$cv->id => $cv->original_name.($cv->is_primary ? ' (primary)' : '')])->all()" :value="optional($cvs->firstWhere('is_primary', true) ?? $cvs->first())->id" required />
                                <x-textarea name="cover_note" label="Cover note (optional)" rows="4" hint="Two or three lines on why you fit. Up to 2,000 characters." />
                                <button type="submit" class="btn-gold btn-block">Send application</button>
                            </form>
                        @endif
                    </section>
                @elseif (auth()->user()->isEmployer())
                    <section class="card text-sm text-ink-600">You are signed in as an employer. <a href="{{ route('employer.jobs.index') }}">Manage your jobs</a>.</section>
                @endif
            @else
                <section class="card">
                    <h2 class="text-base font-semibold">Apply in one tap</h2>
                    <p class="mt-1 text-sm text-ink-600">Create a free account, upload your CV and see how well you match this job.</p>
                    <a href="{{ route('register') }}" class="btn-gold btn-block mt-3 no-underline">Create account</a>
                    <a href="{{ route('login') }}" class="btn-ghost btn-block mt-2 no-underline">I already have an account</a>
                </section>
            @endauth
        </aside>
    </div>
@endsection
