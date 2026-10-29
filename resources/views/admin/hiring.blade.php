@extends('layouts.admin')
@section('title', 'Hiring requests')
@section('content')
    <x-page-header title="Hiring requests" subtitle="Employer requests for shortlists." />

    <div class="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1" role="navigation" aria-label="Filter by status">
        <a href="{{ route('admin.hiring.index') }}" class="chip whitespace-nowrap no-underline {{ ! $status ? 'bg-ink-900 text-white' : '' }}">All</a>
        @foreach (\App\Models\HiringRequest::STATUSES as $key => $label)
            <a href="{{ route('admin.hiring.index', ['status' => $key]) }}" class="chip whitespace-nowrap no-underline {{ $status === $key ? 'bg-ink-900 text-white' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if ($requests->isEmpty())
        <x-empty title="No hiring requests" icon="inbox">Requests from the public form and employer accounts appear here.</x-empty>
    @else
        <ul class="space-y-3">
            @foreach ($requests as $r)
                <li class="card">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-ink-900">{{ $r->role_title }} <span class="text-ink-500">× {{ $r->headcount }}</span></p>
                            <p class="text-sm text-ink-600">{{ $r->company_name }} · {{ $r->contact_name }}</p>
                            <p class="text-xs text-ink-500"><a href="mailto:{{ $r->contact_email }}">{{ $r->contact_email }}</a>@if ($r->contact_phone) · <a href="tel:{{ $r->contact_phone }}">{{ $r->contact_phone }}</a>@endif</p>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <x-badge :color="match ($r->status) { 'new' => 'blue', 'accepted' => 'green', 'in_progress' => 'amber', 'fulfilled' => 'green', default => 'slate' }">{{ $r->statusLabel() }}</x-badge>
                            @if ($r->urgency === 'high')<x-badge color="red">Urgent</x-badge>@endif
                        </div>
                    </div>
                    <dl class="mt-3 grid gap-2 text-sm sm:grid-cols-3">
                        <div><dt class="text-xs text-ink-500">Location</dt><dd>{{ $r->location ?: 'Any' }}</dd></div>
                        <div><dt class="text-xs text-ink-500">Budget</dt><dd>{{ $r->budget ?: 'Not given' }}</dd></div>
                        <div><dt class="text-xs text-ink-500">Skills</dt><dd>{{ $r->required_skills ?: 'Not given' }}</dd></div>
                    </dl>
                    @if ($r->description)<p class="mt-2 whitespace-pre-line text-sm text-ink-700">{{ $r->description }}</p>@endif

                    <button type="button" class="btn-ghost btn-sm mt-3" data-toggle="#manage-{{ $r->id }}" aria-expanded="false" data-allow-resubmit>Manage</button>
                    <form id="manage-{{ $r->id }}" hidden method="POST" action="{{ route('admin.hiring.update', $r) }}" class="mt-3 grid gap-3 border-t border-ink-100 pt-3 sm:grid-cols-2">
                        @csrf @method('PUT')
                        <x-select name="status" label="Status" :options="\App\Models\HiringRequest::STATUSES" :value="$r->status" required />
                        <x-select name="assigned_to" label="Assigned to" :options="$admins->pluck('name', 'id')->all()" :value="$r->assigned_to" placeholder="Unassigned" />
                        <x-textarea name="admin_notes" label="Internal notes" :value="$r->admin_notes" rows="3" class="sm:col-span-2" />
                        <div class="sm:col-span-2"><button class="btn-primary btn-sm" type="submit">Save</button></div>
                    </form>
                </li>
            @endforeach
        </ul>
        @include('partials.pager', ['p' => $requests])
    @endif
@endsection
