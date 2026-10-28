@extends('layouts.admin')
@section('title', 'Admin dashboard')
@section('content')
    <x-page-header title="Admin dashboard" subtitle="Leads, hiring requests and platform activity." />

    <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
        <x-stat label="New leads" :value="$stats['new_leads']" :href="route('admin.leads.index', ['stage' => 'new'])" tone="gold" />
        <x-stat label="New hiring requests" :value="$stats['new_requests']" :href="route('admin.hiring.index', ['status' => 'new'])" tone="gold" />
        <x-stat label="Candidates" :value="$stats['candidates']" :href="route('admin.users.index', ['role' => 'candidate'])" />
        <x-stat label="Employers" :value="$stats['employers']" :href="route('admin.employers.index')" />
        <x-stat label="Open jobs" :value="$stats['open_jobs']" />
        <x-stat label="Applications" :value="$stats['applications']" />
        <x-stat label="CVs uploaded" :value="$stats['cvs']" />
        <x-stat label="Avg. profile complete" :value="$stats['avg_completeness'].'%'" />
    </div>

        @include('admin._weekly')
    @php
        $bars = function (array $rows) {
            $max = max(1, max(array_column($rows, 'total') ?: [0]));
            return $max;
        };
    @endphp

    <div class="grid gap-5 lg:grid-cols-3">
        <section class="card" aria-labelledby="funnel">
            <h2 id="funnel" class="text-base font-semibold">Lead pipeline</h2>
            @php $max = $bars($stageRows); @endphp
            <ul class="mt-3 space-y-2.5">
                @foreach ($stageRows as $row)
                    <li>
                        <a href="{{ route('admin.leads.index', ['stage' => $row['value']]) }}" class="block no-underline">
                            <div class="flex justify-between text-sm"><span class="font-medium text-ink-700">{{ $row['label'] }}</span><span class="font-semibold text-ink-900">{{ $row['total'] }}</span></div>
                            <div class="mt-1 h-2 rounded-full bg-ink-100"><div class="h-full rounded-full bg-cobalt-600" style="width: {{ round($row['total'] / $max * 100) }}%"></div></div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="card" aria-labelledby="types">
            <h2 id="types" class="text-base font-semibold">Leads by type</h2>
            @php $max = $bars($typeRows); @endphp
            <ul class="mt-3 space-y-2.5">
                @foreach ($typeRows as $row)
                    <li>
                        <div class="flex justify-between text-sm"><span class="font-medium text-ink-700">{{ $row['label'] }}</span><span class="font-semibold text-ink-900">{{ $row['total'] }}</span></div>
                        <div class="mt-1 h-2 rounded-full bg-ink-100"><div class="h-full rounded-full bg-gold-500" style="width: {{ round($row['total'] / $max * 100) }}%"></div></div>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="card" aria-labelledby="apps">
            <h2 id="apps" class="text-base font-semibold">Applications by status</h2>
            @php $max = $bars($statusRows); @endphp
            <ul class="mt-3 space-y-2.5">
                @foreach ($statusRows as $row)
                    <li>
                        <div class="flex justify-between text-sm"><span class="font-medium text-ink-700">{{ $row['label'] }}</span><span class="font-semibold text-ink-900">{{ $row['total'] }}</span></div>
                        <div class="mt-1 h-2 rounded-full bg-ink-100"><div class="h-full rounded-full bg-green-500" style="width: {{ round($row['total'] / $max * 100) }}%"></div></div>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <section class="mt-6" aria-labelledby="hot">
        <div class="mb-2 flex items-end justify-between"><h2 id="hot" class="text-lg font-bold">Hottest new leads</h2><a href="{{ route('admin.leads.index') }}" class="text-sm font-semibold">All leads</a></div>
        @if ($hotLeads->isEmpty())
            <x-empty title="No new leads" icon="inbox">New study-abroad, job-seeker and employer leads will show up here.</x-empty>
        @else
            <ul class="card-flat divide-y divide-ink-100 overflow-hidden">
                @foreach ($hotLeads as $lead)
                    <li><a href="{{ route('admin.leads.show', $lead) }}" class="flex items-center gap-3 px-4 py-3 no-underline hover:bg-ink-50">
                        <x-match-ring :score="$lead->score" size="40" />
                        <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold text-ink-900">{{ $lead->name }}</span><span class="block truncate text-xs text-ink-500">{{ $lead->typeLabel() }} · {{ $lead->created_at->diffForHumans() }}</span></span>
                        <x-status-badge :status="$lead->stage" />
                    </a></li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
