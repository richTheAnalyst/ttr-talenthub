@extends('layouts.app')
@section('title', 'Sign in')
@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="h-page">Welcome back</h1>
        <p class="muted mt-1">Sign in to see your matches, applications and alerts.</p>
        <form method="POST" action="{{ route('login') }}" class="card mt-5 space-y-4">
            @csrf
            <x-field name="email" label="Email" type="email" required autocomplete="email" inputmode="email" autofocus />
            <x-field name="password" label="Password" type="password" required autocomplete="current-password" />
            <div class="flex items-center justify-between">
                <x-checkbox name="remember">Keep me signed in</x-checkbox>
                <a href="{{ route('password.request') }}" class="text-sm font-medium">Forgot password?</a>
            </div>
            <button class="btn-primary btn-block" type="submit">Sign in</button>
        </form>
        <p class="mt-4 text-center text-sm text-ink-600">New here? <a href="{{ route('register') }}" class="font-semibold">Create an account</a> · <a href="{{ route('register', ['role' => 'employer']) }}" class="font-semibold">I am hiring</a></p>
    </div>
@endsection
