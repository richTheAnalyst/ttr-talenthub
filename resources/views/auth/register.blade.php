@extends('layouts.app')
@section('title', $role === 'employer' ? 'Create employer account' : 'Create your account')
@section('content')
    <div class="mx-auto max-w-md">
        <div class="mb-4 grid grid-cols-2 gap-1 rounded-xl bg-ink-100 p-1 text-center text-sm font-semibold" role="tablist">
            <a href="{{ route('register', ['interest' => $interest]) }}" class="rounded-lg py-2 no-underline {{ $role === 'candidate' ? 'bg-white text-ink-900 shadow-sm' : 'text-ink-600' }}" @if ($role === 'candidate') aria-current="page" @endif>I am looking</a>
            <a href="{{ route('register', ['role' => 'employer']) }}" class="rounded-lg py-2 no-underline {{ $role === 'employer' ? 'bg-white text-ink-900 shadow-sm' : 'text-ink-600' }}" @if ($role === 'employer') aria-current="page" @endif>I am hiring</a>
        </div>

        <h1 class="h-page">{{ $role === 'employer' ? 'Start hiring' : 'Create your profile' }}</h1>
        <p class="muted mt-1">{{ $role === 'employer' ? 'Post jobs and get matched candidates.' : 'Free. Takes about a minute.' }}</p>

        <form method="POST" action="{{ route('register') }}" class="card mt-5 space-y-4">
            @csrf
            <input type="hidden" name="role" value="{{ $role }}">
            <x-field name="name" label="Full name" required autocomplete="name" />
            @if ($role === 'employer')
                <x-field name="company_name" label="Company name" required autocomplete="organization" />
            @endif
            <x-field name="email" label="Email" type="email" required autocomplete="email" inputmode="email" />
            <x-field name="phone" label="Phone" type="tel" autocomplete="tel" inputmode="tel" hint="Optional. Advisers and employers call this number." />
            @if ($role === 'candidate')
                <x-select name="interest_type" label="I am interested in" :options="config('talenthub.interest_types')" :value="$interest" required />
            @endif
            <x-field name="password" label="Password" type="password" required autocomplete="new-password" hint="At least 8 characters, with letters and numbers." />
            <x-field name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password" />
            <x-checkbox name="terms">I agree to the terms of use and that TTR may contact me about my application, jobs and study options.</x-checkbox>
            <button class="btn-primary btn-block" type="submit">Create account</button>
        </form>
        <p class="mt-4 text-center text-sm text-ink-600">Already registered? <a href="{{ route('login') }}" class="font-semibold">Sign in</a></p>
    </div>
@endsection
