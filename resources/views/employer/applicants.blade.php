@extends('layouts.employer')
@section('title', 'Applicants: '.$job->title)
@section('content')
    <a href="{{ route('employer.jobs.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm font-medium"><x-icon name="chevron" class="h-4 w-4 rotate-180"/> My jobs</a>
    <x-page-header :title="$job->title" :subtitle="$applications->count().' '.\Illuminate\Support\Str::plural('applicant', $applications->count()).', best match first'">
        <x-badge :color="$job->status === 'open' ? 'green' : 'slate'">{{ ucfirst($job->status) }}</x-badge>
        <a href="{{ route('employer.jobs.edit', $job) }}" class="btn-ghost btn-sm no-underline">Edit job</a>
    </x-page-header>

    <div class="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1" role="navigation" aria-label="Filter by status">
        <a href="{{ route('employer.jobs.applicants', $job) }}" class="chip whitespace-nowrap no-underline {{ ! $status ? 'bg-ink-900 text-white' : '' }}">All</a>
        @foreach (\App\Enums\ApplicationStatus::cases() as $s)
            <a href="{{ route('employer.jobs.applicants', [$job, 'status' => $s->value]) }}" class="chip whitespace-nowrap no-underline {{ $status === $s->value ? 'bg-ink-900 text-white' : '' }}">{{ $s->label() }}</a>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
        <section aria-label="Applicants">
            @if ($applications->isEmpty())
                <x-empty title="No applicants" icon="users">{{ $status ? 'No applicants with this status.' : 'Applications will appear here as candidates apply.' }}</x-empty>
            @else
                <ul class="space-y-3">
                    @foreach ($applications as $a)
                        @php $profile = $a->candidate->candidateProfile; @endphp
                        <li class="card" id="application-{{ $a->id }}">
                            <div class="flex items-start gap-3">
                                <x-match-ring :score="$a->match_score" size="52" />
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold text-ink-900">{{ $a->candidate->name }}</p>
                                    <p class="text-sm text-ink-600">{{ $profile?->headline ?: 'No headline' }}</p>
                                    <p class="text-xs text-ink-500">{{ $profile?->location }}@if ($profile?->years_experience) · {{ $profile->years_experience }} yrs experience @endif · applied {{ $a->created_at->diffForHumans() }}</p>
                                </div>
                                <x-status-badge :status="$a->status" />
                            </div>

                            @if ($profile && $profile->skills->isNotEmpty())
                                <p class="mt-3 flex flex-wrap gap-1.5">@foreach ($profile->skills->take(8) as $skill)<span class="chip">{{ $skill->name }}</span>@endforeach</p>
                            @endif
                            @if ($a->cover_note)<p class="mt-3 whitespace-pre-line rounded-xl bg-ink-50 p-3 text-sm text-ink-700">{{ $a->cover_note }}</p>@endif

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                @if ($a->cv)<a href="{{ route('cv.download', $a->cv) }}" class="btn-ghost btn-sm no-underline"><x-icon name="doc" class="h-4 w-4"/> Download CV</a>@endif
                                <a href="mailto:{{ $a->candidate->email }}" class="btn-ghost btn-sm no-underline">Email</a>
                                @if ($a->candidate->phone)<a href="tel:{{ $a->candidate->phone }}" class="btn-ghost btn-sm no-underline">Call</a>@endif
                            </div>

                            @include('employer._interview')

                            @if ($a->status !== \App\Enums\ApplicationStatus::Withdrawn)
                                <form method="POST" action="{{ route('employer.applications.status', $a) }}" class="mt-4 grid gap-2 border-t border-ink-100 pt-4 sm:grid-cols-[12rem_1fr_auto]">
                                    @csrf @method('PATCH')
                                    <x-select name="status" :options="\App\Enums\ApplicationStatus::employerChoices()" :value="$a->status->value" aria-label="Move to status" />
                                    <x-field name="note" placeholder="Note to candidate (optional)" aria-label="Note to candidate" />
                                    <button class="btn-primary" type="submit">Update</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <aside aria-labelledby="suggest">
            <h2 id="suggest" class="mb-2 text-lg font-bold">Candidates who fit</h2>
            @if (! $job->isOpen())
                <p class="muted">Publish the job to see suggested candidates.</p>
            @elseif ($suggestions->isEmpty())
                <x-empty title="No suggestions yet" icon="spark">We will show candidates who match but have not applied.</x-empty>
            @else
                <ul class="space-y-3">
                    @foreach ($suggestions as $s)
                        <li class="card">
                            <div class="flex items-center gap-3">
                                <x-match-ring :score="$s['score']" size="44" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold text-ink-900">{{ $s['profile']->user->name }}</p>
                                    <p class="truncate text-xs text-ink-500">{{ $s['profile']->headline ?: ($s['profile']->desired_title ?: 'Candidate') }}</p>
                                </div>
                            </div>
                            @if ($s['matched_skills'])<p class="mt-2 flex flex-wrap gap-1.5">@foreach (array_slice($s['matched_skills'], 0, 4) as $skill)<span class="chip chip-ok">{{ $skill }}</span>@endforeach</p>@endif
                            <form method="POST" action="{{ route('employer.jobs.invite', [$job, $s['profile']]) }}" class="mt-3">@csrf<button class="btn-ghost btn-sm btn-block" type="submit">Invite to apply</button></form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </aside>
    </div>
@endsection