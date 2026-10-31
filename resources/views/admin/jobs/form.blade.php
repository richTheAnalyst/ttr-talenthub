@extends('layouts.admin')
@section('title', 'Post a job')
@section('content')
    <x-page-header title="Post a job" subtitle="Goes live straight away. Outsourcing jobs show the worker pay after TTR's {{ rtrim(rtrim(number_format($rate, 2), '0'), '.') }}%." />

    <form method="POST" action="{{ route('admin.jobs.store') }}" class="max-w-3xl space-y-5">
        @csrf
        @if ($hr)<input type="hidden" name="hiring_request_id" value="{{ $hr->id }}">@endif

        <section class="card space-y-4">
            <x-select name="employer_id" label="Employer" :options="$employers->pluck('company_name', 'id')->all()" :value="old('employer_id', $hr?->employer_id)" required placeholder="Choose an employer"
                hint="The job is posted under this employer's name." />
            <x-field name="title" label="Job title" :value="$hr?->role_title" required />
            <x-textarea name="description" label="Description" :value="$hr?->description" rows="8" required hint="At least 30 characters." />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="location" label="Location" :value="$hr?->location" />
                <x-field name="country" label="Country" value="Ghana" />
                <x-select name="job_type" label="Job type" :options="\App\Enums\JobType::options()" :value="$hr?->job_type" required />
                <x-field name="expires_at" label="Closing date" type="date" hint="Optional." />
            </div>
        </section>

        <section class="card space-y-4">
            <h2 class="text-lg font-semibold">Requirements</h2>
            <x-field name="required_skills" label="Required skills" :value="$hr?->required_skills" hint="Separate with commas." />
            <x-field name="optional_skills" label="Nice-to-have skills" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="experience_min" label="Minimum years of experience" type="number" min="0" max="40" value="0" inputmode="numeric" />
                <x-select name="education_level" label="Minimum education" :options="config('talenthub.education_levels')" placeholder="No requirement" />
            </div>
        </section>

        <section class="card space-y-4">
            <h2 class="text-lg font-semibold">Service and money</h2>
            <x-select name="engagement" label="Service" :options="\App\Models\JobPosting::ENGAGEMENTS" :value="$hr?->engagement ?? 'recruitment'" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="client_budget" label="Client monthly budget (outsourcing)" type="number" min="1" :value="$hr?->client_budget" inputmode="numeric"
                    hint="The worker is paid this minus TTR's percentage." />
                <x-field name="placement_fee" label="One-time fee in GHS (recruitment)" type="number" min="0" :value="$hr?->placement_fee" inputmode="numeric"
                    hint="Fixed fee for this role." />
            </div>
            <x-select name="currency" label="Currency" :options="['GHS' => 'GHS', 'USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP']" value="GHS" />
        </section>

        <div class="flex flex-wrap gap-2">
            <button class="btn-gold" type="submit">Post job now</button>
            <a href="{{ route('admin.approvals.index') }}" class="btn-ghost no-underline">Cancel</a>
        </div>
    </form>
@endsection