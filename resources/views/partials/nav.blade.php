@php
    /** Navigation for the current visitor: [label, route, icon, active-pattern]. */
    $user = auth()->user();

    if (! $user) {
        $items = [
            ['Home', 'home', 'home', 'home'],
            ['Jobs', 'jobs.index', 'briefcase', 'jobs.*'],
            ['Study', 'study.create', 'plane', 'study.*'],
            ['Hire', 'hire.create', 'building', 'hire.*'],
            ['Sign in', 'login', 'user', 'login'],
        ];
        $more = [];
    } elseif ($user->isCandidate()) {
        $items = [
            ['Home', 'candidate.dashboard', 'home', 'candidate.dashboard'],
            ['Jobs', 'jobs.index', 'briefcase', 'jobs.*'],
            ['Applied', 'candidate.applications.index', 'doc', 'candidate.applications.*'],
            ['Alerts', 'candidate.alerts.index', 'bell', 'candidate.alerts.*'],
            ['Profile', 'candidate.profile.edit', 'user', 'candidate.profile.*'],
        ];
        $more = [['Matches', 'candidate.matches', 'candidate.matches'], ['My CVs', 'candidate.cvs.index', 'candidate.cvs.*'], ['Study abroad', 'study.create', 'study.*']];
    } elseif ($user->isEmployer()) {
        $items = [
            ['Home', 'employer.dashboard', 'home', 'employer.dashboard'],
            ['Jobs', 'employer.jobs.index', 'briefcase', 'employer.jobs.*'],
            ['Hiring', 'employer.hiring.index', 'inbox', 'employer.hiring.*'],
            ['Company', 'employer.company.edit', 'building', 'employer.company.*'],
        ];
        $more = [];
    } else {
        $items = [
            ['Home', 'admin.dashboard', 'chart', 'admin.dashboard'],
            ['Leads', 'admin.leads.index', 'inbox', 'admin.leads.*'],
            ['Hiring', 'admin.hiring.index', 'briefcase', 'admin.hiring.*'],
            ['Users', 'admin.users.index', 'users', 'admin.users.*'],
            ['Firms', 'admin.employers.index', 'building', 'admin.employers.*'],
        ];
        $more = [['CVs', 'admin.cvs.index', 'admin.cvs.*']];
    }
@endphp

{{-- Desktop top links --}}
<nav class="hidden items-center gap-1 md:flex" aria-label="Main">
    @foreach ($items as [$label, $route, $icon, $pattern])
        <a href="{{ route($route) }}" class="navlink" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label === 'Firms' ? 'Employers' : ($label === 'Applied' ? 'Applications' : $label) }}</a>
    @endforeach
    @foreach ($more as [$label, $route, $pattern])
        <a href="{{ route($route) }}" class="navlink" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
