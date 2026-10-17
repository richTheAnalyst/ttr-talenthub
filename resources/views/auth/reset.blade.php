@extends('layouts.app')
@section('title', 'Choose a new password')
@section('content')
    <div class="mx-auto max-w-md">
        <h1 class="h-page">Choose a new password</h1>
        <form method="POST" action="{{ route('password.update') }}" class="card mt-5 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-field name="email" label="Email" type="email" :value="$email" required autocomplete="email" />
            <x-field name="password" label="New password" type="password" required autocomplete="new-password" hint="At least 8 characters, with letters and numbers." />
            <x-field name="password_confirmation" label="Confirm new password" type="password" required autocomplete="new-password" />
            <button class="btn-primary btn-block" type="submit">Update password</button>
        </form>
    </div>
@endsection
