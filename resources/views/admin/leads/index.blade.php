@extends('layouts.admin')
@section('title', 'Leads')
@section('content')
    <x-page-header title="Leads" subtitle="Warmest first. Score combines contact details, intent and profile strength.">
        <a href="{{ route('admin.leads.export', array_filter($filters)) }}" class="btn-ghost btn-sm no-underline" data-allow-resubmit>Export CSV</a>
    </x-page-header>

    <form method="GET" class="card mb-5 grid gap-3 sm:grid-cols-[1fr_11rem_11rem_auto]" role="search">
        <div><label for="q" class="label">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Name, email or phone" type="search"></div>
        <x-select name="stage" label="Stage" :options="\App\Enums\LeadStage::options()" :value="$filters['stage'] ?? null" placeholder="All stages" />
        <x-select name="type" label="Type" :options="\App\Models\Lead::TYPES" :value="$filters['type'] ?? null" placeholder="All types" />
        <div class="flex items-end gap-2"><button class="btn-primary" type="submit">Filter</button>@if (array_filter($filters))<a href="{{ route('admin.leads.index') }}" class="btn-ghost no-underline">Clear</a>@endif</div>
    </form>

    @if ($leads->isEmpty())
        <x-empty title="No leads found" icon="inbox">Try clearing the filters.</x-empty>
    @else
        <ul class="space-y-2">
            @foreach ($leads as $lead)
                <li>
                    <a href="{{ route('admin.leads.show', $lead) }}" class="card flex items-center gap-3 no-underline transition hover:border-cobalt-300">
                        <x-match-ring :score="$lead->score" size="44" />
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold text-ink-900">{{ $lead->name }}</span>
                            <span class="block truncate text-sm text-ink-500">{{ $lead->typeLabel() }} · {{ $lead->email ?: $lead->phone ?: 'No contact' }}</span>
                            <span class="block text-xs text-ink-400">{{ $lead->created_at->diffForHumans() }}@if ($lead->assignee) · {{ $lead->assignee->name }}@endif</span>
                        </span>
                        <x-status-badge :status="$lead->stage" />
                    </a>
                </li>
            @endforeach
        </ul>
        @include('partials.pager', ['p' => $leads])
    @endif
@endsection
