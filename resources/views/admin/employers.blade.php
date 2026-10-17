@extends('layouts.admin')
@section('title', 'Employers')
@section('content')
    <x-page-header title="Employers" subtitle="Verified employers get a badge on every job." />
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Company</th><th>Contact</th><th>Jobs</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
                @forelse ($employers as $e)
                    <tr>
                        <td><p class="font-medium text-ink-900">{{ $e->company_name }}</p><p class="text-xs text-ink-500">{{ $e->industry ?: 'Industry not set' }}@if ($e->location) · {{ $e->location }}@endif</p></td>
                        <td><p>{{ $e->user->name }}</p><p class="text-xs text-ink-500">{{ $e->user->email }}</p></td>
                        <td>{{ $e->jobs_count }}</td>
                        <td><x-badge :color="$e->isVerified() ? 'green' : 'amber'">{{ $e->isVerified() ? 'Verified' : 'Unverified' }}</x-badge></td>
                        <td class="text-right"><form method="POST" action="{{ route('admin.employers.verify', $e) }}">@csrf @method('PATCH')<button class="btn-ghost btn-sm" type="submit">{{ $e->isVerified() ? 'Remove verification' : 'Verify' }}</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-ink-500">No employers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @include('partials.pager', ['p' => $employers])
@endsection
