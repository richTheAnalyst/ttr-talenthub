<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name', 'TTR TalentHub') }}</title>
    <meta name="theme-color" content="#101b3a">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    @php $css = public_path('css/app.css'); @endphp
    <link rel="stylesheet" href="{{ asset('css/app.css') }}@if (is_file($css))?v={{ filemtime($css) }}@endif">
    @stack('head')
</head>
<body class="min-h-full bg-ink-50">
@php
    $company = auth()->user()->employer;
    $links = [
        ['Dashboard', 'employer.dashboard', 'chart', 'employer.dashboard'],
        ['My jobs', 'employer.jobs.index', 'briefcase', 'employer.jobs.index|employer.jobs.edit|employer.jobs.applicants'],
        ['Post a job', 'employer.jobs.create', 'plus', 'employer.jobs.create'],
        ['Hiring requests', 'employer.hiring.index', 'users', 'employer.hiring.*'],
        ['Company profile', 'employer.company.edit', 'building', 'employer.company.*'],
    ];
@endphp

    {{-- Mobile top bar --}}
    <header class="sticky top-0 z-30 flex h-14 items-center gap-3 border-b border-ink-800 bg-ink-900 px-4 pt-safe text-white md:hidden">
        <button type="button" id="side-open" class="flex h-11 w-11 items-center justify-center rounded-xl hover:bg-ink-800" aria-label="Open menu" aria-controls="sidebar" data-allow-resubmit>
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <span class="truncate font-display text-lg font-bold">{{ $company->company_name }}</span>
        <a href="{{ route('notifications.index') }}" class="relative ml-auto flex h-11 w-11 items-center justify-center rounded-xl hover:bg-ink-800" aria-label="Notifications">
            <x-icon name="bell"/>
            @if ($unreadCount)<span class="absolute right-1.5 top-1.5 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-gold-400 px-1 text-[11px] font-bold text-ink-900">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>@endif
        </a>
    </header>

    <div id="side-overlay" class="fixed inset-0 z-40 hidden bg-ink-900/50 md:hidden"></div>

    {{-- Sidebar: fixed on desktop, slide-in drawer on phones --}}
    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-ink-900 pt-safe text-white transition-transform duration-200 md:translate-x-0" aria-label="Employer">
        <div class="flex h-14 shrink-0 items-center gap-2 px-5">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white font-display text-lg font-bold text-ink-900">T</span>
            <span class="font-display text-lg font-bold tracking-tight">TalentHub</span>
            <button type="button" id="side-close" class="ml-auto flex h-10 w-10 items-center justify-center rounded-xl hover:bg-ink-800 md:hidden" aria-label="Close menu" data-allow-resubmit>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </div>

        {{-- Company profile card --}}
        <a href="{{ route('employer.company.edit') }}" class="mx-3 mb-1 mt-2 flex items-center gap-3 rounded-2xl bg-ink-800 p-3 no-underline hover:bg-ink-700">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gold-400 font-display text-xl font-bold text-ink-900">{{ mb_strtoupper(mb_substr($company->company_name ?? 'C', 0, 1)) }}</span>
            <span class="min-w-0">
                <span class="block truncate text-sm font-semibold text-white">{{ $company->company_name }}</span>
                <span class="block truncate text-xs text-ink-300">{{ collect([$company->industry, $company->location])->filter()->implode(' · ') ?: 'Add company details' }}</span>
                @if ($company->isVerified())
                    <span class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-green-300"><x-icon name="shield" class="h-3.5 w-3.5"/> Verified</span>
                @else
                    <span class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-amber-300"><x-icon name="clock" class="h-3.5 w-3.5"/> Pending verification</span>
                @endif
            </span>
        </a>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-3">
            @foreach ($links as [$label, $route, $icon, $pattern])
                @php $active = request()->routeIs(...explode('|', $pattern)); @endphp
                <a href="{{ route($route) }}" @if ($active) aria-current="page" @endif
                   class="flex min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm font-medium no-underline {{ $active ? 'bg-ink-800 text-gold-300' : 'text-ink-200 hover:bg-ink-800 hover:text-white' }}">
                    <x-icon :name="$icon" class="h-5 w-5"/> {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="shrink-0 border-t border-ink-800 p-3">
            <a href="{{ route('notifications.index') }}" class="hidden min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm font-medium text-ink-200 no-underline hover:bg-ink-800 hover:text-white md:flex">
                <x-icon name="bell" class="h-5 w-5"/> Notifications
                @if ($unreadCount)<span class="ml-auto rounded-full bg-gold-400 px-2 text-xs font-bold text-ink-900">{{ $unreadCount }}</span>@endif
            </a>
            <p class="truncate px-3 pt-2 text-xs text-ink-400">{{ auth()->user()->name }} · {{ auth()->user()->email }}</p>
            <form method="POST" action="{{ route('logout') }}" data-allow-resubmit>
                @csrf
                <button type="submit" class="flex min-h-[44px] w-full items-center gap-3 rounded-xl px-3 text-sm font-medium text-ink-200 hover:bg-ink-800 hover:text-white">
                    <x-icon name="logout" class="h-5 w-5"/> Sign out
                </button>
            </form>
        </div>
    </aside>

    <main id="main" class="px-4 py-5 pb-12 sm:py-8 md:ml-64 md:px-8">
        <div class="mx-auto max-w-6xl">
            @if (session('status'))
                <div data-flash role="status" class="mb-4 flex items-start gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">
                    <x-icon name="check" class="mt-0.5 h-5 w-5 shrink-0"/><span>{{ session('status') }}</span>
                </div>
            @endif
            @if ($errors->any())
                <div role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                    <ul class="list-disc space-y-0.5 pl-5">@foreach (array_unique($errors->all()) as $message)<li>{{ $message }}</li>@endforeach</ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <script src="{{ asset('js/app.js') }}" defer></script>
    <script>
        (function () {
            var side = document.getElementById('sidebar'), overlay = document.getElementById('side-overlay');
            function open() { side.classList.remove('-translate-x-full'); overlay.classList.remove('hidden'); }
            function close() { side.classList.add('-translate-x-full'); overlay.classList.add('hidden'); }
            document.getElementById('side-open').addEventListener('click', open);
            document.getElementById('side-close').addEventListener('click', close);
            overlay.addEventListener('click', close);
        })();
    </script>
    @stack('scripts')
</body>
</html>