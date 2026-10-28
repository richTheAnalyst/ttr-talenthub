{{-- Last 7 days vs the 7 days before. Needs $week (WeeklySummary::build()). --}}
<section class="card mb-6" aria-labelledby="week">
    <div class="flex items-baseline justify-between gap-3">
        <h2 id="week" class="text-base font-semibold">This week</h2>
        <span class="text-xs text-ink-500">Last 7 days vs the week before</span>
    </div>
    <dl class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ($week as $r)
            <div class="rounded-xl bg-ink-50 p-3">
                <dt class="text-xs text-ink-500">{{ $r['label'] }}</dt>
                <dd class="mt-0.5 flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-ink-900">{{ number_format($r['now']) }}</span>
                    @if ($r['delta'] !== 0)
                        <span class="text-xs font-semibold {{ $r['delta'] > 0 ? 'text-green-700' : 'text-red-600' }}">{{ $r['delta'] > 0 ? '▲' : '▼' }} {{ abs($r['delta']) }}</span>
                    @else
                        <span class="text-xs text-ink-400">no change</span>
                    @endif
                </dd>
            </div>
        @endforeach
    </dl>
</section>