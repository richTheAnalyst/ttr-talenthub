{{-- <x-job-card :job="$job" :score="72" :matched="['PHP']" /> Needs employer loaded. --}}
@props(['job', 'score' => null, 'matched' => [], 'missing' => []])
<a href="{{ route('jobs.show', $job) }}" class="card block transition hover:border-cobalt-300 hover:shadow-md">
    <div class="flex items-start gap-3">
        <div class="min-w-0 flex-1">
            <h3 class="truncate text-base font-semibold">{{ $job->title }}</h3>
            <p class="mt-0.5 flex flex-wrap items-center gap-x-2 text-sm text-ink-600">
                <span>{{ $job->employer->company_name }}</span>
                @if ($job->employer->isVerified())
                    <span class="inline-flex items-center gap-0.5 text-xs font-medium text-green-700"><x-icon name="shield" class="h-3.5 w-3.5"/> Verified</span>
                @endif
            </p>
        </div>
        @if ($score !== null)<x-match-ring :score="$score" />@endif
    </div>
    <div class="mt-3 flex flex-wrap items-center gap-1.5">
        <x-badge color="blue">{{ $job->job_type->label() }}</x-badge>
        @if ($job->location)<span class="chip"><x-icon name="pin" class="mr-1 h-3.5 w-3.5"/>{{ $job->location }}</span>@endif
        @if ($job->salaryRange())<span class="chip">{{ $job->salaryRange() }}</span>@endif
    </div>
    @if ($matched)
        <p class="mt-3 flex flex-wrap gap-1.5">
            @foreach (array_slice($matched, 0, 4) as $skill)<span class="chip chip-ok">{{ $skill }}</span>@endforeach
            @foreach (array_slice($missing, 0, 2) as $skill)<span class="chip chip-miss">Missing: {{ $skill }}</span>@endforeach
        </p>
    @endif
    <p class="mt-3 text-xs text-ink-500">Posted {{ ($job->published_at ?? $job->created_at)->diffForHumans() }}@if ($job->expires_at) · closes {{ $job->expires_at->format('j M') }}@endif</p>
</a>
