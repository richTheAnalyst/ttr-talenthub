@extends('layouts.app')
@section('title', 'Jobs, study abroad and hiring')

@section('content')
@php
    $covers = [
        'from-coral-400 to-coral-600', 'from-cobalt-400 to-cobalt-700', 'from-gold-300 to-gold-600',
        'from-green-400 to-green-700', 'from-purple-400 to-purple-700', 'from-pink-400 to-pink-700',
    ];
    $categories = [
        ['All jobs', 'briefcase', route('jobs.index')],
        ['Full-time', 'clock', route('jobs.index', ['type' => 'full_time'])],
        ['Part-time', 'user', route('jobs.index', ['type' => 'part_time'])],
        ['Contract', 'doc', route('jobs.index', ['type' => 'contract'])],
        ['Internships', 'spark', route('jobs.index', ['type' => 'internship'])],
        ['Study abroad', 'plane', route('study.create')],
        ['Hire talent', 'building', route('hire.create')],
    ];
    $destinations = [
        ['United Kingdom', 'from-cobalt-500 to-ink-900'], ['Canada', 'from-coral-500 to-coral-700'],
        ['Germany', 'from-gold-400 to-coral-600'], ['Australia', 'from-green-500 to-cobalt-700'],
        ['Ireland', 'from-green-400 to-green-700'], ['Netherlands', 'from-purple-500 to-coral-600'],
    ];
@endphp

    {{-- Hero + search pill --}}
    <section class="-mx-4 -mt-5 bg-gradient-to-b from-coral-50 to-white px-4 pb-8 pt-10 text-center sm:mx-0 sm:mt-0 sm:rounded-3xl sm:px-10 sm:pb-12 sm:pt-14">
       {{--  <h1 class="mx-auto max-w-3xl font-display text-4xl font-bold leading-tight text-ink-900 sm:text-5xl">Find a job you'll love.<br class="hidden sm:block"> Or the country you'll study in.</h1>
        <p class="mx-auto mt-3 max-w-xl text-base text-ink-600">Upload your CV once, get matched to open roles, and track every application in one place.</p> --}}

        <x-location-list />
        <x-job-keyword-list />
        <form id="hero-search" action="{{ route('jobs.index') }}" method="GET" role="search"
              class="mx-auto mt-7 flex max-w-3xl flex-col gap-2 rounded-3xl border border-ink-200 bg-white p-3 text-left shadow-lg md:flex-row md:items-center md:gap-0 md:rounded-full md:p-2">
            <label class="flex-1 rounded-2xl px-4 py-2 hover:bg-ink-50 md:rounded-full">
                <span class="block text-xs font-bold text-ink-900">What</span>
                   <input name="q" type="search" list="job-keywords" autocomplete="off" placeholder="Job title, skill or company" class="w-full border-0 bg-transparent p-0 text-sm text-ink-700 placeholder:text-ink-400 focus:ring-0">
            </label>
            <span class="hidden h-8 w-px bg-ink-200 md:block"></span>
            <label class="flex-1 rounded-2xl px-4 py-2 hover:bg-ink-50 md:rounded-full">
                <span class="block text-xs font-bold text-ink-900">Where</span>
                   <input name="location" type="search" list="locations" autocomplete="off" placeholder="City or country" class="w-full border-0 bg-transparent p-0 text-sm text-ink-700 placeholder:text-ink-400 focus:ring-0">
            </label>
            <span class="hidden h-8 w-px bg-ink-200 md:block"></span>
            <label class="flex-1 rounded-2xl px-4 py-2 hover:bg-ink-50 md:rounded-full">
                <span class="block text-xs font-bold text-ink-900">Type</span>
                <select name="type" class="w-full border-0 bg-transparent p-0 text-sm text-ink-700 focus:ring-0">
                    <option value="">Any type</option>
                    @foreach (\App\Enums\JobType::cases() as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach
                </select>
            </label>
            <button type="submit" class="flex min-h-[48px] items-center justify-center gap-2 rounded-full bg-coral-500 px-6 font-semibold text-white transition hover:bg-coral-600 md:h-12 md:w-12 md:px-0" aria-label="Search jobs">
                <x-icon name="search" class="h-5 w-5"/><span class="md:hidden">Search</span>
            </button>
        </form>

        
        <p class="mt-5 flex flex-wrap justify-center gap-x-5 gap-y-1 text-sm text-ink-600">
            <span><strong class="text-ink-900">{{ number_format($stats['jobs']) }}</strong> open jobs</span>
            <span><strong class="text-ink-900">{{ number_format($stats['employers']) }}</strong> employers</span>
            <span><strong class="text-ink-900">{{ number_format($stats['candidates']) }}</strong> candidates</span>
        </p>
    </section>

    {{-- Category row --}}
    <nav class="-mx-4 mt-6 overflow-x-auto border-b border-ink-100 px-4 sm:mx-0 sm:px-0" aria-label="Browse by type">
        <ul class="flex min-w-max justify-start gap-7 sm:justify-center">
            @foreach ($categories as [$label, $icon, $url])
                <li>
                    <a href="{{ $url }}" class="group flex flex-col items-center gap-1.5 border-b-2 border-transparent px-1 pb-3 pt-1 text-xs font-medium text-ink-500 no-underline transition hover:border-ink-300 hover:text-ink-900">
                        <x-icon :name="$icon" class="h-6 w-6"/>{{ $label }}
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- Latest jobs: photo-style tiles --}}
    <section class="mt-8" aria-labelledby="latest">
        <div class="mb-4 flex items-end justify-between">
            <h2 id="latest" class="text-2xl font-bold">Latest jobs</h2>
            <a href="{{ route('jobs.index') }}" class="text-sm font-semibold text-ink-900 underline">Show all</a>
        </div>
        @if ($jobs->isEmpty())
            <x-empty title="No open jobs yet" icon="briefcase">Check back soon, or create an account to get alerts when jobs are posted.</x-empty>
        @else
            <div class="grid grid-cols-1 gap-x-5 gap-y-7 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($jobs as $job)
                    <a href="{{ route('jobs.show', $job) }}" class="group block no-underline">
                        <div class="relative aspect-[4/3] overflow-hidden rounded-2xl bg-gradient-to-br {{ $covers[$job->id % count($covers)] }}">
                            <span class="absolute -bottom-4 -right-2 font-display text-[7rem] font-bold leading-none text-white/25 transition group-hover:scale-110">{{ mb_strtoupper(mb_substr($job->employer->company_name, 0, 1)) }}</span>
                            <span class="absolute left-3 top-3 rounded-full bg-white px-3 py-1 text-xs font-semibold text-ink-900 shadow-sm">{{ $job->job_type->label() }}</span>
                            @if ($job->employer->isVerified())
                                <span class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-full bg-white text-green-700 shadow-sm" title="Verified employer"><x-icon name="shield" class="h-4 w-4"/></span>
                            @endif
                        </div>
                        <div class="mt-3">
                            <h3 class="truncate text-base font-semibold text-ink-900">{{ $job->title }}</h3>
                            <p class="truncate text-sm text-ink-500">{{ $job->employer->company_name }}@if ($job->location) · {{ $job->location }}@endif</p>
                            <p class="mt-0.5 text-sm text-ink-900">@if ($job->salaryRange())<span class="font-semibold">{{ $job->salaryRange() }}</span>@else<span class="text-ink-500">Salary not listed</span>@endif</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Study abroad destinations --}}
    <section class="mt-12" aria-labelledby="study">
        <div class="mb-4 flex items-end justify-between">
            <div>
                <h2 id="study" class="text-2xl font-bold">Study abroad</h2>
                <p class="text-sm text-ink-500">An adviser calls you within two working days.</p>
            </div>
            <a href="{{ route('study.create') }}" class="btn-primary btn-sm shrink-0 no-underline">Start enquiry</a>
        </div>
        <div class="-mx-4 flex snap-x gap-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0">
            @foreach ($destinations as [$country, $grad])
                <a href="{{ route('study.create') }}" class="group relative block h-44 w-44 shrink-0 snap-start overflow-hidden rounded-2xl bg-gradient-to-br {{ $grad }} no-underline sm:h-52 sm:w-56">
                    <x-icon name="plane" class="absolute -right-3 -top-3 h-24 w-24 -rotate-12 text-white/20 transition group-hover:scale-110"/>
                    <span class="absolute bottom-3 left-3 right-3 font-display text-lg font-bold text-white">{{ $country }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section class="mt-12" aria-labelledby="how">
        <h2 id="how" class="mb-4 text-2xl font-bold">How it works</h2>
        <ol class="grid gap-6 sm:grid-cols-3">
            @foreach ([['Build your profile', 'Add your skills, experience and one CV. It takes five minutes.', 'user'], ['Get matched', 'We score every open job against your profile and alert you to the best ones.', 'spark'], ['Apply and track', 'Apply in one tap and follow each application from submitted to hired.', 'check']] as [$title, $body, $icon])
                <li>
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-coral-50 text-coral-600"><x-icon :name="$icon"/></span>
                    <h3 class="mt-3 text-lg font-semibold">{{ $title }}</h3>
                    <p class="mt-1 text-sm text-ink-600">{{ $body }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Employer call to action --}}
    <section class="mt-12 overflow-hidden rounded-3xl bg-gradient-to-br from-coral-500 to-coral-700 p-8 text-white sm:p-12">
        <div class="max-w-xl">
            <h2 class="font-display text-3xl font-bold text-white sm:text-4xl">Hiring? Meet your next great hire.</h2>
            <p class="mt-2 text-coral-100">Post a role or send us a hiring request and receive a shortlist of matched candidates.</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ route('hire.create') }}" class="inline-flex min-h-[48px] items-center rounded-xl bg-white px-6 font-semibold text-coral-700 no-underline hover:bg-coral-50">Send a hiring request</a>
                <a href="{{ route('register') }}" class="inline-flex min-h-[48px] items-center rounded-xl border border-white/60 px-6 font-semibold text-white no-underline hover:bg-white/10">Create an account</a>
            </div>
        </div>
    </section>
@endsection



@push('scripts')
    {{-- Floating search button: appears bottom-right once the big search bar scrolls out of view. --}}
    <button type="button" id="scroll-search" aria-label="Search jobs" tabindex="-1"
            class="pointer-events-none fixed bottom-24 right-4 z-40 flex h-14 w-14 translate-y-4 items-center justify-center rounded-full bg-coral-500 text-white opacity-0 shadow-xl transition duration-200 hover:bg-coral-600 md:bottom-6 md:right-6">
        <x-icon name="search" class="h-6 w-6"/>
    </button>

    <script>
        (function () {
            var form = document.getElementById('hero-search'), btn = document.getElementById('scroll-search');
            if (!form || !btn || !('IntersectionObserver' in window)) { return; }
            var hide = ['pointer-events-none', 'translate-y-4', 'opacity-0'];
            new IntersectionObserver(function (entries) {
                var visible = entries[0].isIntersecting;
                hide.forEach(function (c) { btn.classList.toggle(c, visible); });
                btn.tabIndex = visible ? -1 : 0;
            }, { rootMargin: '-64px 0px 0px 0px' }).observe(form);
            btn.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                setTimeout(function () { var i = form.querySelector('input[name=q]'); if (i) { i.focus(); } }, 350);
            });
        })();
    </script>
@endpush