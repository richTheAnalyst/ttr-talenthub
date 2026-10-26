{{-- Suggestion dropdown for the job keyword box. Add list="job-keywords" to the input; any text can still be typed. --}}
@php
    $keywords = \Illuminate\Support\Facades\Cache::remember('job-keyword-suggestions', 600, function () {
        $open = \App\Models\JobPosting::open();

        $titles = (clone $open)->distinct()->limit(150)->pluck('title');
        $companies = \App\Models\Employer::whereIn('id', (clone $open)->select('employer_id'))->limit(100)->pluck('company_name');
        $skills = \App\Models\Skill::orderBy('name')->limit(150)->pluck('name');

        return collect()->merge($titles)->merge($companies)->merge($skills)
            ->map(fn ($k) => trim((string) $k))->filter()
            ->unique(fn ($k) => mb_strtolower($k))->sort()->values()->all();
    });
@endphp
<datalist id="job-keywords">
    @foreach ($keywords as $keyword)
        <option value="{{ $keyword }}"></option>
    @endforeach
</datalist>