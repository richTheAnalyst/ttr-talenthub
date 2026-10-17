@extends('layouts.app')
@section('title', 'Application: '.$application->job->title)
@section('content')
    <a href="{{ route('candidate.applications.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm font-medium"><x-icon name="chevron" class="h-4 w-4 rotate-180"/> My applications</a>

    <div class="grid gap-5 lg:grid-cols-[1fr_20rem]">
        <div class="space-y-5">
            <section class="card">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h1 class="text-2xl font-bold">{{ $application->job->title }}</h1>
                        <p class="text-ink-600">{{ $application->job->employer->company_name }}@if ($application->job->location) · {{ $application->job->location }}@endif</p>
                    </div>
                    <x-match-ring :score="$application->match_score" size="56" />
                </div>
                <div class="mt-5"><x-tracker :status="$application->status" /></div>
            </section>

            <section class="card">
                <h2 class="text-lg font-semibold">History</h2>
                <ol class="mt-3 space-y-4 border-l-2 border-ink-200 pl-4">
                    @foreach ($application->histories->reverse() as $h)
                        <li class="relative">
                            <span class="absolute -left-[1.4rem] top-1 h-3 w-3 rounded-full border-2 border-white bg-cobalt-600"></span>
                            <p class="text-sm font-semibold text-ink-900">{{ $h->to_status->label() }}</p>
                            @if ($h->note)<p class="text-sm text-ink-600">{{ $h->note }}</p>@endif
                            <p class="text-xs text-ink-400">{{ $h->created_at->format('j M Y, H:i') }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>

            @if ($application->cover_note)
                <section class="card"><h2 class="text-lg font-semibold">Your cover note</h2><p class="mt-2 whitespace-pre-line text-ink-700">{{ $application->cover_note }}</p></section>
            @endif
        </div>

        <aside class="space-y-5">
            <section class="card">
                <h2 class="text-base font-semibold">Details</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-ink-500">Applied</dt><dd>{{ $application->created_at->format('j M Y') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-ink-500">CV sent</dt><dd class="text-right">@if ($application->cv)<a href="{{ route('cv.download', $application->cv) }}">{{ \Illuminate\Support\Str::limit($application->cv->original_name, 24) }}</a>@else Removed @endif</dd></div>
                </dl>
                <a href="{{ route('jobs.show', $application->job) }}" class="btn-ghost btn-block mt-4 no-underline">View job</a>
            </section>

            @can('withdraw', $application)
                @unless ($application->status->isFinal())
                    <form method="POST" action="{{ route('candidate.applications.withdraw', $application) }}" data-confirm="Withdraw this application? The employer will no longer consider it.">
                        @csrf @method('PATCH')
                        <button class="btn-danger btn-block" type="submit">Withdraw application</button>
                    </form>
                @endunless
            @endcan
        </aside>
    </div>
@endsection
