@php
    $user = auth()->user();
    // Same list as partials.nav; kept as data so both stay in step.
    $tabs = match (true) {
        ! $user => [
            ['Home', 'home', 'home', 'home'], ['Jobs', 'jobs.index', 'briefcase', 'jobs.*'], ['Study', 'study.create', 'plane', 'study.*'],
            ['Hire', 'hire.create', 'building', 'hire.*'], ['Sign in', 'login', 'user', 'login'],
        ],
        $user->isCandidate() => [
            ['Home', 'candidate.dashboard', 'home', 'candidate.dashboard'], ['Jobs', 'jobs.index', 'briefcase', 'jobs.*'],
            ['Applied', 'candidate.applications.index', 'doc', 'candidate.applications.*'], ['Alerts', 'candidate.alerts.index', 'bell', 'candidate.alerts.*'],
            ['Profile', 'candidate.profile.edit', 'user', 'candidate.profile.*'],
        ],
        $user->isEmployer() => [
            ['Home', 'employer.dashboard', 'home', 'employer.dashboard'], ['Jobs', 'employer.jobs.index', 'briefcase', 'employer.jobs.*'],
            ['Hiring', 'employer.hiring.index', 'inbox', 'employer.hiring.*'], ['Company', 'employer.company.edit', 'building', 'employer.company.*'],
        ],
        default => [
            ['Home', 'admin.dashboard', 'chart', 'admin.dashboard'], ['Leads', 'admin.leads.index', 'inbox', 'admin.leads.*'],
            ['Hiring', 'admin.hiring.index', 'briefcase', 'admin.hiring.*'], ['Users', 'admin.users.index', 'users', 'admin.users.*'],
            ['Firms', 'admin.employers.index', 'building', 'admin.employers.*'],
        ],
    };
@endphp
<nav class="tabbar" aria-label="Main">
    <div class="mx-auto flex max-w-lg">
        @foreach ($tabs as [$label, $route, $icon, $pattern])
            <a href="{{ route($route) }}" class="tab" @if (request()->routeIs($pattern)) aria-current="page" @endif>
                <x-icon :name="$icon"/>
                <span>{{ $label }}</span>
                <span class="tab-dot"></span>
            </a>
        @endforeach
    </div>
</nav>
