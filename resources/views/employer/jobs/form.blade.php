@extends('layouts.employer')
@section('title', $job ? 'Edit job' : 'Post a job')
@section('content')
    <x-page-header :title="$job ? 'Edit job' : 'Post a job'" subtitle="Add the skills you need. Candidates are scored against them." />

    <form method="POST" action="{{ $job ? route('employer.jobs.update', $job) : route('employer.jobs.store') }}" class="max-w-3xl space-y-5">
        @csrf
        @if ($job) @method('PUT') @endif

        <section class="card space-y-4">
            <x-field name="title" label="Job title" :value="$job?->title" required placeholder="e.g. Customer Service Officer" />
            <x-textarea name="description" label="Description" :value="$job?->description" rows="8" required hint="Responsibilities, working hours and what success looks like. At least 30 characters." />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="location" label="Location" :value="$job?->location ?? auth()->user()->employer?->location" placeholder="e.g. Accra" />
                <x-field name="country" label="Country" :value="$job?->country ?? 'Ghana'" />
                <x-select name="job_type" label="Job type" :options="\App\Enums\JobType::options()" :value="$job?->job_type" required />
                <x-field name="expires_at" label="Closing date" type="date" :value="$job?->expires_at?->format('Y-m-d')" hint="Optional. The job closes automatically." />
            </div>
        </section>

        <section class="card space-y-4">
            <h2 class="text-lg font-semibold">Requirements</h2>
            <x-field name="required_skills" label="Required skills" :value="$requiredSkills ?? ''" hint="Separate with commas. These count most in the match score." />
            <x-field name="optional_skills" label="Nice-to-have skills" :value="$optionalSkills ?? ''" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="experience_min" label="Minimum years of experience" type="number" min="0" max="40" :value="$job?->experience_min ?? 0" inputmode="numeric" />
                <x-select name="education_level" label="Minimum education" :options="config('talenthub.education_levels')" :value="$job?->education_level" placeholder="No requirement" />
            </div>
        </section>

        <section class="card space-y-4">
            <h2 class="text-lg font-semibold">Pay</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-field name="salary_min" label="Monthly minimum" type="number" min="0" :value="$job?->salary_min" inputmode="numeric" />
                <x-field name="salary_max" label="Monthly maximum" type="number" min="0" :value="$job?->salary_max" inputmode="numeric" />
                <x-select name="currency" label="Currency" :options="['GHS' => 'GHS', 'USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP']" :value="$job?->currency ?? 'GHS'" />
            </div>
        </section>

        <div class="flex flex-wrap gap-2">
            <button class="btn-gold" type="submit" name="action" value="publish">{{ $job && $job->status === 'open' ? 'Save changes' : 'Publish job' }}</button>
            @unless ($job && $job->status === 'open')
                <button class="btn-ghost" type="submit" name="action" value="draft">Save as draft</button>
            @endunless
            <a href="{{ route('employer.jobs.index') }}" class="btn-ghost no-underline">Cancel</a>
        </div>
    </form>
@endsection
