<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@hasSection('title')@yield('title') · @endif{{ config('app.name', 'TTR TalentHub') }}</title>
    <meta name="description" content="@yield('description', 'Find jobs, track applications, study abroad and hire talent with TTR TalentHub.')">
    <meta name="theme-color" content="#101b3a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TalentHub">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="32x32">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&display=swap" media="print" onload="this.media='all'">
    @php $css = public_path('css/app.css'); @endphp
    <link rel="stylesheet" href="{{ asset('css/app.css') }}@if (is_file($css))?v={{ filemtime($css) }}@endif">
    @stack('head')
</head>
<body class="min-h-full">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-white focus:px-3 focus:py-2">Skip to content</a>

    <div id="offline-banner" hidden class="bg-amber-100 px-4 py-2 text-center text-sm font-medium text-amber-900" role="status">You are offline. Some things may not load until you reconnect.</div>

    <header class="sticky top-0 z-30 border-b border-ink-800 bg-ink-900 pt-safe text-white">
        <div class="mx-auto flex h-14 max-w-6xl items-center gap-3 px-4">
            <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="flex items-center gap-2 text-white no-underline">
                <x-logo />

            <div class="ml-4 hidden md:block">
                @include('partials.nav')
            </div>

            <div class="ml-auto flex items-center gap-1">
                @stack('header_actions')
                @auth
                    <a href="{{ route('notifications.index') }}" class="relative flex h-11 w-11 items-center justify-center rounded-xl text-ink-100 hover:bg-ink-800" aria-label="Notifications{{ $unreadCount ? ', '.$unreadCount.' unread' : '' }}">
                        <x-icon name="bell"/>
                        @if ($unreadCount)
                            <span class="absolute right-1.5 top-1.5 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-gold-400 px-1 text-[11px] font-bold text-ink-900">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                        @endif
                    </a>
                    <form method="POST" action="{{ route('logout') }}" data-allow-resubmit>
                        @csrf
                        <button type="submit" class="flex h-11 items-center gap-1.5 rounded-xl px-3 text-sm font-medium text-ink-100 hover:bg-ink-800" aria-label="Sign out">
                            <x-icon name="logout" class="h-5 w-5"/><span class="hidden sm:inline">Sign out</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-xl px-3 py-2 text-sm font-medium text-ink-100 hover:bg-ink-800 md:inline-block">Sign in</a>
                    <a href="{{ route('register') }}" class="btn-gold btn-sm">Join free</a>
                @endauth
            </div>
        </div>
    </header>

    <main id="main" class="mx-auto max-w-6xl px-4 py-5 pb-safe sm:py-8">
        @if (session('status'))
            <div data-flash role="status" class="mb-4 flex items-start gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-900">
                <x-icon name="check" class="mt-0.5 h-5 w-5 shrink-0"/><span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                <p class="font-semibold">{{ $errors->count() === 1 ? 'There is a problem:' : 'Please fix these problems:' }}</p>
                <ul class="mt-1 list-disc space-y-0.5 pl-5">
                    @foreach (array_unique($errors->all()) as $message)<li>{{ $message }}</li>@endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="hidden border-t border-ink-200 py-8 text-center text-sm text-ink-500 md:block">
        TTR TalentHub · Jobs, study abroad and hiring in one place ·
        <a href="{{ route('jobs.index') }}">Jobs</a> · <a href="{{ route('study.create') }}">Study abroad</a> · <a href="{{ route('hire.create') }}">Hire talent</a>
    </footer>

    {{-- Install prompt, shown only when the browser says the app can be installed. --}}
    <div id="install-banner" hidden class="fixed inset-x-3 bottom-24 z-50 mx-auto max-w-md rounded-2xl border border-ink-200 bg-white p-4 shadow-xl md:bottom-6 md:left-auto md:right-6" role="dialog" aria-label="Install TalentHub">
        <p class="font-display font-semibold text-ink-900">Add TalentHub to your home screen</p>
        <p class="mt-1 text-sm text-ink-600">Open it like an app and get job alerts faster.</p>
        <div class="mt-3 flex gap-2">
            <button id="install-accept" type="button" class="btn-primary btn-sm" data-allow-resubmit>Install</button>
            <button id="install-dismiss" type="button" class="btn-ghost btn-sm" data-allow-resubmit>Not now</button>
        </div>
    </div>

    @include('partials.tabbar')

    <script src="{{ asset('js/app.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
