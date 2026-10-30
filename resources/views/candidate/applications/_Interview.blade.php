{{-- Interview card for the candidate. Expects $application. --}}
@php
    $interviews = $application->interviews;
    $current = $interviews->first(fn ($i) => $i->isActive() && $i->scheduled_at->isFuture());
    $past = $interviews->reject(fn ($i) => $current && $i->id === $current->id);
@endphp

@if ($current || $past->isNotEmpty())
    <section class="card" id="interview">
        <h2 class="text-lg font-semibold">Interview</h2>

        @if ($current)
            <div class="mt-3 rounded-xl bg-cobalt-50 p-4">
                <p class="text-base font-bold text-ink-900">{{ $current->whenText() }}</p>
                <p class="text-sm text-ink-700">{{ \App\Models\Interview::DURATIONS[$current->duration_minutes] ?? $current->duration_minutes.' min' }} · {{ $current->modeLabel() }}</p>
                <p class="mt-1 break-words text-sm text-ink-700">
                    @if (str_starts_with((string) $current->details, 'http'))
                        <a href="{{ $current->details }}" target="_blank" rel="noopener">{{ $current->details }}</a>
                    @else
                        {{ $current->details }}
                    @endif
                </p>
                @if ($current->note)<p class="mt-2 whitespace-pre-line text-sm text-ink-600">"{{ $current->note }}"</p>@endif

                @if ($current->status === 'confirmed')
                    <p class="mt-3"><x-badge color="green">You confirmed this interview</x-badge></p>
                @else
                    <div class="mt-4 grid gap-3">
                        <form method="POST" action="{{ route('candidate.interviews.respond', $current) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="response" value="confirm">
                            <button class="btn-primary btn-block" type="submit">Confirm this time</button>
                        </form>
                        <form method="POST" action="{{ route('candidate.interviews.respond', $current) }}" class="grid gap-2">
                            @csrf @method('PATCH')
                            <input type="hidden" name="response" value="decline">
                            <input class="input" name="reason" maxlength="500" placeholder="Reason, or a time that suits you better (optional)" aria-label="Reason for declining">
                            <button class="btn-ghost btn-block" type="submit">I cannot make it</button>
                        </form>
                    </div>
                @endif
            </div>
        @endif

        @foreach ($past as $i)
            <p class="mt-3 text-sm text-ink-500">{{ $i->whenText() }}: {{ $i->status }}@if ($i->decline_reason) ("{{ $i->decline_reason }}")@endif</p>
        @endforeach
    </section>
@endif