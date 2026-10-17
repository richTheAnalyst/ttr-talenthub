@extends('layouts.employer')
@section('title', 'Hiring requests')
@section('content')
    <x-page-header title="Hiring requests" subtitle="Ask the TTR team to shortlist candidates for you.">
        <a href="{{ route('employer.hiring.create') }}" class="btn-gold no-underline"><x-icon name="plus" class="h-5 w-5"/> New request</a>
    </x-page-header>
    @if ($requests->isEmpty())
        <x-empty title="No requests yet" icon="inbox">Describe the role and our team will send you a shortlist.</x-empty>
    @else
        <ul class="space-y-3">
            @foreach ($requests as $r)
                <li class="card">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0"><p class="font-semibold text-ink-900">{{ $r->role_title }} <span class="text-ink-500">× {{ $r->headcount }}</span></p><p class="text-sm text-ink-500">{{ $r->location ?: 'Any location' }} · sent {{ $r->created_at->diffForHumans() }}</p></div>
                        <x-badge :color="match ($r->status) { 'new' => 'blue', 'in_progress' => 'amber', 'fulfilled' => 'green', default => 'slate' }">{{ $r->statusLabel() }}</x-badge>
                    </div>
                </li>
            @endforeach
        </ul>
        @include('partials.pager', ['p' => $requests])
    @endif
@endsection
