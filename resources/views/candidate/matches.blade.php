@extends('layouts.app')
@section('title', 'Job matches')
@section('content')
    <x-page-header title="Matches for you" subtitle="Open jobs you have not applied to, best fit first." />

    @if ($matches->isEmpty())
        <x-empty :title="'No matches above '.config('talenthub.matching.recommend_min_score').'% yet'" icon="spark">
            Matches are based on your skills, desired title, location, experience and education. The more you add, the better they get.
            <div class="mt-3"><a href="{{ route('candidate.profile.edit') }}" class="btn-primary btn-sm no-underline">Improve my profile</a></div>
        </x-empty>
    @else
        <div class="grid gap-3 md:grid-cols-2">
            @foreach ($matches as $m)
                <x-job-card :job="$m['job']" :score="$m['score']" :matched="$m['matched_skills']" :missing="$m['missing_skills']" />
            @endforeach
        </div>
    @endif
@endsection
