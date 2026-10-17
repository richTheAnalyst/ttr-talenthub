@extends('layouts.employer')
@section('title', 'New hiring request')
@section('content')
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('employer.hiring.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm font-medium"><x-icon name="chevron" class="h-4 w-4 rotate-180"/> Hiring requests</a>
        <h1 class="h-page">New hiring request</h1>
        <p class="muted mt-1">Our team will reply with a shortlist of matched candidates.</p>
        @include('leads._hire-form', ['employer' => $employer])
    </div>
@endsection
