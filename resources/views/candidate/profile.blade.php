@extends('layouts.app')
@section('title', 'My profile')
@section('content')
    <x-page-header title="My profile" subtitle="Employers and advisers see this. Better detail means better matches.">
        <x-match-ring :score="$profile->completeness" size="52" />
    </x-page-header>

    <form method="POST" action="{{ route('candidate.profile.update') }}" class="space-y-5">
        @csrf @method('PUT')

        <section class="card space-y-4">
            <h2 class="text-lg font-semibold">About you</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="name" label="Full name" :value="$user->name" required autocomplete="name" />
                <x-field name="phone" label="Phone" type="tel" :value="$user->phone" autocomplete="tel" inputmode="tel" />
            </div>
            <x-field name="headline" label="Headline" :value="$profile->headline" placeholder="e.g. Customer service professional with 3 years experience" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="location" label="Where you live" :value="$profile->location" placeholder="e.g. Accra" list="locations" autocomplete="off" /><x-location-list />
                <x-field name="desired_location" label="Preferred location" :value="$profile->desired_location" list="locations" autocomplete="off" />
            </div>
            <x-textarea name="bio" label="Short bio" :value="$profile->bio" rows="4" />
        </section>

        <section class="card space-y-4">
            <h2 class="text-lg font-semibold">Skills and experience</h2>
            <x-textarea name="skills" label="Skills" :value="$skillsText" rows="2" hint="Separate with commas, e.g. PHP, Laravel, Customer service. Skills drive 50% of your match score." />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="years_experience" label="Years of experience" type="number" min="0" max="60" :value="$profile->years_experience" inputmode="numeric" />
                <x-select name="education_level" label="Highest education" :options="config('talenthub.education_levels')" :value="$profile->education_level" placeholder="Choose" />
            </div>
            <x-field name="field_of_study" label="Field of study" :value="$profile->field_of_study" />
        </section>

        <section class="card space-y-4">
            <h2 class="text-lg font-semibold">What you want</h2>
            <x-select name="interest_type" label="I am interested in" :options="config('talenthub.interest_types')" :value="$profile->interest_type" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field name="desired_title" label="Desired job title" :value="$profile->desired_title" />
                <x-select name="desired_job_type" label="Job type" :options="\App\Enums\JobType::options()" :value="$profile->desired_job_type" placeholder="Any" />
                <x-field name="desired_location" label="Preferred location" :value="$profile->desired_location" />
                <x-field name="expected_salary" label="Expected monthly salary (GHS)" type="number" min="0" :value="$profile->expected_salary" inputmode="numeric" />
                <x-select name="availability" label="Available to start" :options="config('talenthub.availabilities')" :value="$profile->availability" placeholder="Choose" />
            </div>
        </section>

        <section class="card space-y-4">
            <h2 class="text-lg font-semibold">Study abroad</h2>
            <p class="muted">Only needed if you chose studying abroad above.</p>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-select name="study_destination" label="Destination" :options="array_combine(config('talenthub.study_destinations'), config('talenthub.study_destinations'))" :value="$profile->study_destination" placeholder="Choose" />
                <x-select name="study_level" label="Level" :options="config('talenthub.study_levels')" :value="$profile->study_level" placeholder="Choose" />
                <x-select name="study_intake" label="Intake" :options="config('talenthub.study_intakes')" :value="$profile->study_intake" placeholder="Choose" />
            </div>
        </section>

        <section class="card space-y-3">
            <h2 class="text-lg font-semibold">Privacy</h2>
            <x-checkbox name="is_searchable" :checked="$profile->is_searchable">Let verified employers find my profile and invite me to apply.</x-checkbox>
            <x-checkbox name="consent_marketing" :checked="$profile->consent_marketing">Send me job tips, events and study offers by email or WhatsApp.</x-checkbox>
        </section>

        <div class="sticky bottom-20 z-20 md:static">
            <button class="btn-primary btn-block shadow-lg md:w-auto md:shadow-none" type="submit">Save profile</button>
        </div>
    </form>
@endsection
