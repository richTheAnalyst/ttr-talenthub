{{-- Shows why a job scored as it did. @include('partials.match-breakdown', ['match' => $match]) --}}
@php
    $labels = ['skills' => 'Skills', 'title' => 'Job title', 'location' => 'Location', 'experience' => 'Experience', 'job_type' => 'Job type', 'education' => 'Education'];
@endphp
<div class="space-y-2.5">
    @foreach ($labels as $key => $label)
        @continue(! isset($match['breakdown'][$key]))
        @php $pct = (int) round($match['breakdown'][$key]['ratio'] * 100); @endphp
        <div>
            <div class="flex justify-between text-xs"><span class="font-medium text-ink-700">{{ $label }}</span><span class="text-ink-500">{{ $pct }}%</span></div>
            <div class="mt-1 h-2 overflow-hidden rounded-full bg-ink-100" role="presentation"><div class="h-full rounded-full {{ $pct >= 70 ? 'bg-green-500' : ($pct >= 40 ? 'bg-cobalt-500' : 'bg-ink-300') }}" style="width: {{ $pct }}%"></div></div>
        </div>
    @endforeach
</div>
@if (! empty($match['matched_skills']))
    <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-ink-500">Skills you have</p>
    <p class="mt-1.5 flex flex-wrap gap-1.5">@foreach ($match['matched_skills'] as $skill)<span class="chip chip-ok">{{ $skill }}</span>@endforeach</p>
@endif
@if (! empty($match['missing_skills']))
    <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-ink-500">Required skills to add</p>
    <p class="mt-1.5 flex flex-wrap gap-1.5">@foreach ($match['missing_skills'] as $skill)<span class="chip chip-miss">{{ $skill }}</span>@endforeach</p>
@endif
