@extends('layouts.app')
@section('title', 'Job alerts')
@section('content')
    <x-page-header title="Job alerts" subtitle="We notify you the moment a matching job is posted, or send one digest each morning." />

    <div class="grid gap-5 lg:grid-cols-[22rem_1fr]">
        <form method="POST" action="{{ route('candidate.alerts.store') }}" class="card space-y-4 lg:self-start">
            @csrf
            <h2 class="text-base font-semibold">New alert</h2>
            <x-field name="keywords" label="Keywords" placeholder="e.g. laravel, accountant" hint="Separate with commas. Leave empty for every job." />
            <x-field name="location" label="Location" placeholder="e.g. Kumasi" list="locations" autocomplete="off" /><x-location-list />
            <x-select name="job_type" label="Job type" :options="\App\Enums\JobType::options()" placeholder="Any type" />
            <div>
                <label for="min_match" class="label">Minimum match: <output id="min_match_out" for="min_match">{{ old('min_match', config('talenthub.matching.alert_min_score')) }}</output>%</label>
                <input id="min_match" name="min_match" type="range" min="0" max="100" step="5" value="{{ old('min_match', config('talenthub.matching.alert_min_score')) }}" class="w-full accent-cobalt-600"
                       oninput="document.getElementById('min_match_out').textContent = this.value">
                <p class="hint">Only alert me about jobs that fit at least this well.</p>
                @error('min_match')<p class="error">{{ $message }}</p>@enderror
            </div>
            <x-select name="frequency" label="How often" :options="['instant' => 'As soon as posted', 'daily' => 'Daily digest, 7am']" value="instant" required />
            <button class="btn-primary btn-block" type="submit" @disabled($alerts->count() >= $max)>Create alert</button>
            @if ($alerts->count() >= $max)<p class="hint">You have {{ $max }} alerts, the maximum.</p>@endif
        </form>

        <div>
            @if ($alerts->isEmpty())
                <x-empty title="No alerts yet" icon="bell">Create one and we will do the watching for you.</x-empty>
            @else
                <ul class="space-y-3">
                    @foreach ($alerts as $alert)
                        <li class="card">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-ink-900">{{ $alert->summary() }}</p>
                                    <p class="text-sm text-ink-500">At least {{ $alert->min_match }}% match · {{ $alert->frequency === 'daily' ? 'daily digest' : 'instant' }}</p>
                                </div>
                                <x-badge :color="$alert->is_active ? 'green' : 'slate'">{{ $alert->is_active ? 'On' : 'Paused' }}</x-badge>
                            </div>
                            <div class="mt-3 flex gap-2">
                                <form method="POST" action="{{ route('candidate.alerts.toggle', $alert) }}">@csrf @method('PATCH')<button class="btn-ghost btn-sm" type="submit">{{ $alert->is_active ? 'Pause' : 'Turn on' }}</button></form>
                                <form method="POST" action="{{ route('candidate.alerts.destroy', $alert) }}" data-confirm="Delete this alert?">@csrf @method('DELETE')<button class="btn-danger btn-sm" type="submit">Delete</button></form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection
