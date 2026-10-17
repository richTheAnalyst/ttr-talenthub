@extends('layouts.app')
@section('title', 'Reset password')
@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="h-page">Reset your password</h1>
        <p class="muted mt-1">Enter your email and we will send you a link.</p>
        <form method="POST" action="{{ route('password.email') }}" class="card mt-5 space-y-4">
            @csrf
            <x-field name="email" label="Email" type="email" required autocomplete="email" inputmode="email" autofocus />
            <button class="btn-primary btn-block" type="submit">Send reset link</button>
        </form>
        <p class="mt-4 text-center text-sm"><a href="{{ route('login') }}" class="font-semibold">Back to sign in</a></p>
    </div>
@endsection
