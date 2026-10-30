@extends('layouts.admin')
@section('title', 'Approvals')
@section('content')
    <x-page-header title="Approvals" subtitle="Jobs and interviews wait here until you approve them." />

    <h2 class="mb-2 text-lg font-bold">Jobs ({{ $jobs->count() }})</h2>
    @if ($jobs->isEmpty())
        <x-empty title="No jobs waiting" icon="briefcase">New or edited jobs appear here for approval.</x-empty>
    @else
        <ul class="mb-8 space-y-3">
            @foreach ($jobs as $job)
                <li class="card">
                    <p class="font-semibold text-ink-900">{{ $job->title }}</p>
                    <p class="text-sm text-ink-600">{{ $job->employer->company_name }}@if ($job->location) · {{ $job->location }}@endif · {{ $job->job_type->label() }}@if ($job->salaryRange()) · {{ $job->salaryRange() }}@endif</p>
                    <p class="mt-2 text-sm">
                        <x-badge :color="$job->isOutsourcing() ? 'blue' : 'violet'">{{ \App\Models\JobPosting::ENGAGEMENTS[$job->engagement] ?? ucfirst($job->engagement) }}</x-badge>
                        @if ($job->isOutsourcing() && $job->client_budget)
                            <span class="ml-1 text-ink-700">Client budget {{ $job->currency }} {{ number_format($job->client_budget) }} a month · TTR keeps {{ rtrim(rtrim(number_format($rate, 2), '0'), '.') }}% ({{ $job->currency }} {{ number_format($job->client_budget - $job->workerPay($rate)) }}) · worker is paid <strong>{{ $job->currency }} {{ number_format($job->workerPay($rate)) }}</strong></span>
                        @endif
                    </p>
                    @error('job')<p class="error">{{ $message }}</p>@enderror
                    <p class="mt-2 line-clamp-4 whitespace-pre-line text-sm text-ink-700">{{ $job->description }}</p>
                    @if ($job->skills->isNotEmpty())<p class="mt-2 flex flex-wrap gap-1.5">@foreach ($job->skills->take(10) as $skill)<span class="chip">{{ $skill->name }}</span>@endforeach</p>@endif
                    <div class="mt-3 grid gap-2 border-t border-ink-100 pt-3 sm:grid-cols-[auto_1fr_auto] sm:items-end">
                        <form method="POST" action="{{ route('admin.approvals.jobs.approve', $job) }}" class="flex flex-wrap items-end gap-2">
                            @csrf @method('PATCH')
                            @unless ($job->isOutsourcing())
                                <div>
                                    <label class="label" for="fee-{{ $job->id }}">Fixed fee ({{ $job->currency }})</label>
                                    <input class="input w-36" id="fee-{{ $job->id }}" name="placement_fee" type="number" min="0" required value="{{ old('placement_fee', $job->placement_fee) }}">
                                </div>
                            @endunless
                            <button class="btn-primary btn-sm" type="submit">Approve</button>
                        </form>
                        <form method="POST" action="{{ route('admin.approvals.jobs.reject', $job) }}" class="contents">
                            @csrf @method('PATCH')
                            <input class="input" name="reason" required maxlength="500" placeholder="Reason, shown to the employer" aria-label="Reason for not approving">
                            <button class="btn-ghost btn-sm" type="submit">Do not approve</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <h2 class="mb-2 text-lg font-bold">Interviews ({{ $interviews->count() }})</h2>
    @if ($interviews->isEmpty())
        <x-empty title="No interviews waiting" icon="users">Interviews proposed by employers appear here when approval is switched on in Settings.</x-empty>
    @else
        <ul class="space-y-3">
            @foreach ($interviews as $i)
                @php $app = $i->application; @endphp
                <li class="card">
                    <p class="font-semibold text-ink-900">{{ $app->candidate->name }} · {{ $app->job->title }}</p>
                    <p class="text-sm text-ink-600">Proposed by {{ $app->job->employer->company_name }}</p>
                    <p class="mt-1 text-sm font-medium text-ink-900">{{ $i->whenText() }} · {{ \App\Models\Interview::DURATIONS[$i->duration_minutes] ?? $i->duration_minutes.' min' }} · {{ $i->modeLabel() }}</p>
                    <p class="break-words text-sm text-ink-600">{{ $i->details }}</p>
                    @if ($i->note)<p class="mt-1 text-sm text-ink-600">"{{ $i->note }}"</p>@endif
                    <div class="mt-3 grid gap-2 border-t border-ink-100 pt-3 sm:grid-cols-[auto_1fr_auto] sm:items-end">
                        <form method="POST" action="{{ route('admin.approvals.interviews.approve', $i) }}">@csrf @method('PATCH')<button class="btn-primary btn-sm" type="submit">Approve and send</button></form>
                        <form method="POST" action="{{ route('admin.approvals.interviews.reject', $i) }}" class="contents">
                            @csrf @method('PATCH')
                            <input class="input" name="reason" required maxlength="500" placeholder="Reason, shown to the employer" aria-label="Reason for not approving">
                            <button class="btn-ghost btn-sm" type="submit">Do not approve</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
@endsection