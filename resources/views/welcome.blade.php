@extends('layouts.app')
@section('title', 'Jobs, study abroad and hiring')

@section('content')
    {{-- Hero --}}
    <section class="-mx-4 -mt-5 overflow-hidden bg-ink-900 px-4 pb-10 pt-10 text-white sm:mx-0 sm:mt-0 sm:rounded-3xl sm:px-10 sm:py-14">
        <p class="inline-flex items-center gap-1.5 rounded-full bg-ink-800 px-3 py-1 text-xs font-semibold text-gold-300"><x-icon name="spark" class="h-3.5 w-3.5"/> Your career hub</p>
        <h1 class="mt-4 max-w-2xl font-display text-4xl font-bold leading-tight text-white sm:text-5xl">Your next job, or your next country, starts here.</h1>
        <p class="mt-3 max-w-xl text-base text-ink-200">Upload your CV once. Get matched to open roles, track every application and talk to advisers about studying abroad.</p>

        <form action="{{ route('jobs.index') }}" method="GET" class="mt-6 flex max-w-xl flex-col gap-2 sm:flex-row" role="search">
            <label for="hero-q" class="sr-only">Search jobs</label>
            <input id="hero-q" name="q" type="search" placeholder="Job title, skill or company" class="input flex-1 border-transparent">
            <button class="btn-gold" type="submit"><x-icon name="search" class="h-5 w-5"/> Search jobs</button>
        </form>
        <p class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-sm text-ink-300">
            <span><strong class="text-white">{{ number_format($stats['jobs']) }}</strong> open jobs</span>
            <span><strong class="text-white">{{ number_format($stats['employers']) }}</strong> employers</span>
            <span><strong class="text-white">{{ number_format($stats['candidates']) }}</strong> candidates</span>
        </p>
    </section>

    {{-- Three paths --}}
    <section class="mt-8 grid gap-3 md:grid-cols-3" aria-label="What would you like to do?">
        <a href="{{ route('register') }}" class="card block no-underline transition hover:border-cobalt-300 hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-cobalt-50 text-cobalt-600"><x-icon name="briefcase"/></span>
            <h2 class="mt-3 text-lg font-semibold">Find a job</h2>
            <p class="mt-1 text-sm text-ink-600">Create a profile, upload your CV and see a match score on every job.</p>
        </a>
        <a href="{{ route('study.create') }}" class="card block no-underline transition hover:border-cobalt-300 hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gold-100 text-gold-600"><x-icon name="plane"/></span>
            <h2 class="mt-3 text-lg font-semibold">Study abroad</h2>
            <p class="mt-1 text-sm text-ink-600">Tell us your course and destination. An adviser will call you within two working days.</p>
        </a>
        <a href="{{ route('hire.create') }}" class="card block no-underline transition hover:border-cobalt-300 hover:shadow-md">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-green-100 text-green-700"><x-icon name="building"/></span>
            <h2 class="mt-3 text-lg font-semibold">Hire talent</h2>
            <p class="mt-1 text-sm text-ink-600">Post a role or send us a hiring request and receive a shortlist of matched candidates.</p>
        </a>
    </section>

    {{-- Latest jobs --}}
    <section class="mt-10" aria-labelledby="latest">
        <div class="mb-3 flex items-end justify-between">
            <h2 id="latest" class="text-xl font-bold">Latest jobs</h2>
            <a href="{{ route('jobs.index') }}" class="text-sm font-semibold">See all</a>
        </div>
        @if ($jobs->isEmpty())
            <x-empty title="No open jobs yet" icon="briefcase">Check back soon, or create an account to get alerts when jobs are posted.</x-empty>
        @else
            <div class="grid gap-3 md:grid-cols-2">
                @foreach ($jobs as $job)<x-job-card :job="$job" />@endforeach
            </div>
        @endif
    </section>

    {{-- How it works --}}
    <section class="mt-10" aria-labelledby="how">
        <h2 id="how" class="mb-3 text-xl font-bold">How it works</h2>
        <ol class="grid gap-3 sm:grid-cols-3">
            @foreach ([['Build your profile', 'Add your skills, experience and one CV. It takes five minutes.'], ['Get matched', 'We score every open job against your profile and alert you to the best ones.'], ['Apply and track', 'Apply in one tap and follow each application from submitted to hired.']] as $i => [$title, $body])
                <li class="card">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gold-400 text-sm font-bold text-ink-900">{{ $i + 1 }}</span>
                    <h3 class="mt-3 font-semibold">{{ $title }}</h3>
                    <p class="mt-1 text-sm text-ink-600">{{ $body }}</p>
                </li>
            @endforeach
        </ol>
    </section>
@endsection
