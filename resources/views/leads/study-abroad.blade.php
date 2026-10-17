@extends('layouts.app')
@section('title', 'Study abroad')
@section('description', 'Tell us where and what you want to study. A TTR adviser will contact you within two working days.')
@section('content')
    <div class="grid gap-6 lg:grid-cols-[1fr_24rem]">
        <div>
            <h1 class="h-page">Study abroad with expert guidance</h1>
            <p class="muted mt-1 max-w-xl">Share your plans and a TTR adviser will help with course choice, applications, visas and funding. No account needed.</p>

            <form method="POST" action="{{ route('study.store') }}" class="card mt-5 space-y-4">
                @csrf
                <x-honeypot />
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="name" label="Full name" required autocomplete="name" :value="auth()->user()?->name" />
                    <x-field name="phone" label="Phone or WhatsApp" type="tel" required autocomplete="tel" inputmode="tel" :value="auth()->user()?->phone" />
                </div>
                <x-field name="email" label="Email" type="email" required autocomplete="email" inputmode="email" :value="auth()->user()?->email" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-select name="destination" label="Where do you want to study?" :options="array_combine(config('talenthub.study_destinations'), config('talenthub.study_destinations'))" placeholder="Choose a country" required />
                    <x-select name="level" label="Level of study" :options="config('talenthub.study_levels')" placeholder="Choose a level" required />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-select name="intake" label="Preferred intake" :options="config('talenthub.study_intakes')" placeholder="Not sure yet" />
                    <x-field name="field_of_study" label="Course or field" placeholder="e.g. Nursing, Data science" />
                </div>
                <x-textarea name="message" label="Anything else we should know?" rows="3" hint="Budget, scholarship needs, current qualifications." />
                <x-checkbox name="consent">I agree that TTR may contact me about study options. I can opt out at any time.</x-checkbox>
                <button class="btn-gold btn-block sm:w-auto" type="submit">Request a call back</button>
            </form>
        </div>

        <aside class="space-y-3">
            @foreach ([['Free first consultation', 'Understand your options before you spend anything.'], ['Applications handled', 'We check documents, statements and deadlines with you.'], ['Visa and travel support', 'Step-by-step help from offer letter to arrival.']] as [$title, $body])
                <div class="card flex gap-3">
                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700"><x-icon name="check" class="h-5 w-5"/></span>
                    <div><p class="font-semibold text-ink-900">{{ $title }}</p><p class="text-sm text-ink-600">{{ $body }}</p></div>
                </div>
            @endforeach
        </aside>
    </div>
@endsection
