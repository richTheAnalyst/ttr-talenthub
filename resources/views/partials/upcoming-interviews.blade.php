{{-- Next interviews. Expects $upcoming: a collection of rows from App\Services\UpcomingInterviews. --}}
<section class="mb-6" aria-labelledby="upcoming">
    <h2 id="upcoming" class="mb-2 text-lg font-bold">Upcoming interviews</h2>
    @if ($upcoming->isEmpty())
        <div class="card-flat px-4 py-3 text-sm text-ink-600">No interviews scheduled yet.</div>
    @else
        <ul class="card-flat divide-y divide-ink-100 overflow-hidden">
            @foreach ($upcoming as $u)
                <li>
                    <a href="{{ $u['url'] }}" class="flex items-center gap-3 px-4 py-3 no-underline hover:bg-ink-50">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-ink-900">{{ $u['title'] }}</span>
                            <span class="block truncate text-xs text-ink-500">{{ $u['subtitle'] }} · {{ $u['mode'] }}</span>
                            <span class="block text-xs {{ $u['soon'] ? 'font-semibold text-amber-700' : 'text-ink-600' }}">{{ $u['when'] }}@if ($u['soon']) · within 24 hours @endif</span>
                        </span>
                        <x-badge :color="match ($u['status']) { 'confirmed' => 'green', 'proposed' => 'blue', default => 'amber' }">{{ match ($u['status']) { 'confirmed' => 'Confirmed', 'proposed' => 'Awaiting reply', default => 'Needs approval' } }}</x-badge>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</section>