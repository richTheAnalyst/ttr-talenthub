@extends('layouts.app')
@section('title', 'Hire talent')
@section('description', 'Tell us who you need. TTR sends you a shortlist of matched candidates.')
@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="h-page">Tell us who you need</h1>
        <p class="muted mt-1">We screen candidates from TalentHub and send you a shortlist. No account needed, though <a href="{{ route('register', ['role' => 'employer']) }}">an employer account</a> lets you post jobs yourself.</p>

        @include('leads._hire-form', ['employer' => $employer])
    </div>
@endsection
