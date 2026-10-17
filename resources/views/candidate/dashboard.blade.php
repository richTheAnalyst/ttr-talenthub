@extends('layouts.app')
@section('title', 'My dashboard')
@section('content')
    <x-page-header :title="'Hello, '.\Illuminate\Support\Str::before(auth()->user()->name, ' ')" subtitle="Here is what is happening with your job search." />

    {{-- Profile strength --}}
    @php $done = $profile->completeness >= 100; @endphp
    @unless ($done)
        <section class="card mb-5 border-gold-300 bg-gold-100/40" aria-labelledby="strength">
            <div class="flex items-center gap-4">
                <x-match-ring :score="$profile->completeness" size="56" />
                <div class="min-w-0 flex-1">
                    <h2 id="strength" class="text-base font-semibold">Your profile is {{ $profile->completeness }}% complete</h2>
                    <p class="text-sm text-ink-600">Complete profiles get better matches and more employer views.</p>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
                @unless ($hasCv)<a href="{{ route('candidate.cvs.index') }}" class="btn-primary btn-sm no-underline"><x-icon name="upload" class="h-4 w-4"/> Upload CV</a>@endunless
                <a href="{{ route('candidate.profile.edit') }}" class="btn-ghost btn-sm no-underline">Finish profile</a>
            </div>
        </section>
    @endunless

    <div class="mb-6 grid grid-cols-3 gap-3">
        <x-stat label="Applications" :value="$stats['applications']" :href="route('candidate.applications.index')" />
        <x-stat label="In progress" :value="$stats['active']" :href="route('candidate.applications.index')" tone="gold" />
        <x-stat label="Active alerts" :value="$stats['alerts']" :href="route('candidate.alerts.index')" />
    </div>

    @if ($notifications->isNotEmpty())
        <section class="mb-6" aria-labelledby="updates">
            <h2 id="updates" class="mb-2 text-lg font-bold">New for you</h2>
            <ul class="card-flat divide-y divide-ink-100 overflow-hidden">
                @foreach ($notifications as $n)
                    <li><a href="{{ route('notifications.read', $n->id) }}" class="flex items-start gap-3 px-4 py-3 no-underline hover:bg-ink-50">
                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-gold-500"></span>
                        <span><span class="block text-sm font-semibold text-ink-900">{{ $n->data['title'] ?? 'Update' }}</span><span class="block text-sm text-ink-600">{{ $n->data['body'] ?? '' }}</span></span>
                    </a></li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="mb-6" aria-labelledby="matches">
        <div class="mb-2 flex items-end justify-between">
            <h2 id="matches" class="text-lg font-bold">Best matches for you</h2>
            <a href="{{ route('candidate.matches') }}" class="text-sm font-semibold">See all</a>
        </div>
        @if ($matches->isEmpty())
            <x-empty title="No matches yet" icon="spark">Add your skills, location and desired job title so we can score open jobs for you.
                <div class="mt-3"><a href="{{ route('candidate.profile.edit') }}" class="btn-primary btn-sm no-underline">Update profile</a></div>
            </x-empty>
        @else
            <div class="grid gap-3 md:grid-cols-2">
                @foreach ($matches as $m)
                    <x-job-card :job="$m['job']" :score="$m['score']" :matched="$m['matched_skills']" :missing="$m['missing_skills']" />
                @endforeach
            </div>
        @endif
    </section>

    <section aria-labelledby="recent">
        <div class="mb-2 flex items-end justify-between">
            <h2 id="recent" class="text-lg font-bold">Recent applications</h2>
            <a href="{{ route('candidate.applications.index') }}" class="text-sm font-semibold">See all</a>
        </div>
        @if ($applications->isEmpty())
            <x-empty title="You have not applied yet" icon="doc">Browse open jobs and apply in one tap.
                <div class="mt-3"><a href="{{ route('jobs.index') }}" class="btn-primary btn-sm no-underline">Browse jobs</a></div>
            </x-empty>
        @else
            @include('candidate.applications._list', ['applications' => $applications])
        @endif
    </section>

    <section class="mt-6 card flex items-center gap-4">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gold-100 text-gold-600"><x-icon name="plane"/></span>
        <div class="min-w-0 flex-1"><p class="font-semibold text-ink-900">Thinking about studying abroad?</p><p class="text-sm text-ink-600">Talk to an adviser, free.</p></div>
        <a href="{{ route('study.create') }}" class="btn-ghost btn-sm no-underline">Start</a>
    </section>
@endsection
