@extends('layouts.app')
@section('title', 'My applications')
@section('content')
    <x-page-header title="My applications" subtitle="Follow every application from submitted to hired." />

    <div class="-mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1" role="navigation" aria-label="Filter by status">
        <a href="{{ route('candidate.applications.index') }}" class="chip whitespace-nowrap no-underline {{ ! $status ? 'bg-ink-900 text-white' : '' }}">All</a>
        @foreach (\App\Enums\ApplicationStatus::cases() as $s)
            <a href="{{ route('candidate.applications.index', ['status' => $s->value]) }}" class="chip whitespace-nowrap no-underline {{ $status === $s->value ? 'bg-ink-900 text-white' : '' }}">{{ $s->label() }}</a>
        @endforeach
    </div>

    @if ($applications->isEmpty())
        <x-empty title="Nothing here yet" icon="doc">{{ $status ? 'No applications with this status.' : 'Apply to a job and it will show up here.' }}
            <div class="mt-3"><a href="{{ route('jobs.index') }}" class="btn-primary btn-sm no-underline">Browse jobs</a></div>
        </x-empty>
    @else
        @include('candidate.applications._list', ['applications' => $applications])
        @include('partials.pager', ['p' => $applications])
    @endif
@endsection
