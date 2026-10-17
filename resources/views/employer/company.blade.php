@extends('layouts.employer')
@section('title', 'Company details')
@section('content')
    <x-page-header title="Company details" subtitle="Candidates see this on every job you post.">
        @if ($employer->isVerified())<x-badge color="green">Verified</x-badge>@else<x-badge color="amber">Pending verification</x-badge>@endif
    </x-page-header>
    <form method="POST" action="{{ route('employer.company.update') }}" class="card max-w-2xl space-y-4">
        @csrf @method('PUT')
        <x-field name="company_name" label="Company name" :value="$employer->company_name" required />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-field name="industry" label="Industry" :value="$employer->industry" placeholder="e.g. Logistics" />
            <x-field name="location" label="Head office" :value="$employer->location" placeholder="e.g. Accra" />
            <x-field name="website" label="Website" type="url" :value="$employer->website" placeholder="https://" inputmode="url" />
            <x-select name="size" label="Company size" :options="['1-10' => '1 to 10', '11-50' => '11 to 50', '51-200' => '51 to 200', '201-1000' => '201 to 1,000', '1000+' => 'More than 1,000']" :value="$employer->size" placeholder="Choose" />
        </div>
        <x-textarea name="description" label="About the company" :value="$employer->description" rows="5" />
        <button class="btn-primary" type="submit">Save</button>
    </form>
@endsection
