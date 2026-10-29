@extends('layouts.admin')
@section('title', 'Applications')
@section('content')
    <x-page-header title="Applications" subtitle="Review applications, accept them and the candidate is notified straight away." />

    <form method="GET" action="{{ route('admin.applications.index') }}" class="card mb-4 flex flex-col gap-3 sm:flex-row" role="search">
        @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
        <input name="q" value="{{ $q }}" type="search" class="input flex-1" placeholder="Search candidate, email or job title" aria-label="Search applications">
        <button class="btn-primary" type="submit">Search</button>
    </form>

    <div class="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1" role="navigation" aria-label="Filter by status">
        <a href="{{ route('admin.applications.index', array_filter(['q' => $q])) }}" class="chip whitespace-nowrap no-underline {{ ! $status ? 'bg-ink-900 text-white' : '' }}">All ({{ $counts->sum() }})</a>
        @foreach (\App\Enums\ApplicationStatus::cases() as $s)
            <a href="{{ route('admin.applications.index', array_filter(['status' => $s->value, 'q' => $q])) }}" class="chip whitespace-nowrap no-underline {{ $status === $s->value ? 'bg-ink-900 text-white' : '' }}">{{ $s->label() }} ({{ $counts[$s->value] ?? 0 }})</a>
        @endforeach
    </div>

    @if ($applications->isEmpty())
        <x-empty title="No applications here" icon="briefcase">Applications appear as soon as candidates apply for jobs.</x-empty>
    @else
        <ul class="space-y-3">
            @foreach ($applications as $a)
                <li class="card">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-ink-900">{{ $a->candidate->name }}</p>
                            <p class="text-xs text-ink-500">{{ $a->candidate->email }}@if ($a->candidate->phone) · {{ $a->candidate->phone }}@endif</p>
                            <p class="mt-1 text-sm text-ink-700">Applied for <span class="font-medium">{{ $a->job->title }}</span> at {{ $a->job->employer->company_name }}</p>
                            <p class="text-xs text-ink-500">{{ $a->created_at->format('j M Y') }} · {{ $a->match_score }}% match</p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <x-status-badge :status="$a->status" />
                            @if ($a->cv)<a href="{{ route('cv.download', $a->cv) }}" class="btn-ghost btn-sm no-underline">Download CV</a>@endif
                        </div>
                    </div>

                    @if ($a->cover_note)<p class="mt-2 whitespace-pre-line rounded-xl bg-ink-50 p-3 text-sm text-ink-700">{{ $a->cover_note }}</p>@endif

                    @unless ($a->status === \App\Enums\ApplicationStatus::Withdrawn)
                        <div class="mt-3 flex flex-wrap items-end gap-2 border-t border-ink-100 pt-3">
                            @if (in_array($a->status, [\App\Enums\ApplicationStatus::Submitted, \App\Enums\ApplicationStatus::UnderReview], true))
                                <form method="POST" action="{{ route('admin.applications.update', $a) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="accepted">
                                    <button class="btn-primary btn-sm" type="submit">Accept</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.applications.update', $a) }}" class="flex flex-1 flex-wrap items-end gap-2">
                                @csrf @method('PATCH')
                                <select name="status" class="input w-auto" aria-label="Set status">
                                    @foreach ($choices as $k => $l)<option value="{{ $k }}" @selected($a->status->value === $k)>{{ $l }}</option>@endforeach
                                </select>
                                <input name="note" class="input min-w-[10rem] flex-1" maxlength="1000" placeholder="Message to the candidate (optional)" aria-label="Note">
                                <button class="btn-ghost btn-sm" type="submit">Update</button>
                            </form>
                        </div>
                    @endunless
                </li>
            @endforeach
        </ul>
        @include('partials.pager', ['p' => $applications])
    @endif
@endsection