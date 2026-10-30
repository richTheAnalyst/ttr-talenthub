{{-- Interview block for one applicant. Expects $a (Application) from the applicants list. --}}
@php
    $interviews = $a->interviews;
    $current = $interviews->first(fn ($i) => $i->isActive() && $i->scheduled_at->isFuture());
    $last = $interviews->first(fn ($i) => in_array($i->status, ['declined', 'cancelled'], true));
    $formOpen = old('interview_for') == $a->id;
@endphp

@unless ($a->status->isFinal())
    <div class="mt-4 border-t border-ink-100 pt-4">
        <p class="text-sm font-semibold text-ink-900">Interview</p>

        @if ($current)
            <div class="mt-2 rounded-xl bg-ink-50 p-3 text-sm">
                <p class="font-medium text-ink-900">{{ $current->whenText() }} · {{ \App\Models\Interview::DURATIONS[$current->duration_minutes] ?? $current->duration_minutes.' min' }}</p>
                <p class="text-ink-600">{{ $current->modeLabel() }}: {{ $current->details }}</p>
                <p class="mt-1">
                    @if ($current->status === 'confirmed')
                        <x-badge color="green">Confirmed by candidate</x-badge>
                    @else
                        <x-badge color="amber">Waiting for the candidate to confirm</x-badge>
                    @endif
                </p>
                <form method="POST" action="{{ route('employer.interviews.cancel', $current) }}" data-confirm="Cancel this interview? The candidate will be told." class="mt-2">
                    @csrf @method('PATCH')
                    <button class="btn-ghost btn-sm" type="submit">Cancel interview</button>
                </form>
            </div>
        @else
            @if ($last)
                <p class="mt-1 text-sm text-ink-600">
                    Last time ({{ $last->whenText() }}) was {{ $last->status }}@if ($last->decline_reason): "{{ $last->decline_reason }}"@endif.
                </p>
            @endif

            <details class="mt-2" @if ($formOpen) open @endif>
                <summary class="btn-ghost btn-sm inline-flex cursor-pointer">Schedule an interview</summary>
                <form method="POST" action="{{ route('employer.interviews.store', $a) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <input type="hidden" name="interview_for" value="{{ $a->id }}">
                    @if ($formOpen && $errors->any())
                        <p class="error sm:col-span-2">{{ $errors->first() }}</p>
                    @endif
                    <div>
                        <label class="label" for="iv-when-{{ $a->id }}">Date and time (GMT)</label>
                        <input class="input" id="iv-when-{{ $a->id }}" type="datetime-local" name="scheduled_at" required min="{{ now()->format('Y-m-d\TH:i') }}" value="{{ $formOpen ? old('scheduled_at') : '' }}">
                    </div>
                    <div>
                        <label class="label" for="iv-len-{{ $a->id }}">Length</label>
                        <select class="input" id="iv-len-{{ $a->id }}" name="duration_minutes">
                            @foreach (\App\Models\Interview::DURATIONS as $mins => $text)
                                <option value="{{ $mins }}" @selected((int) old('duration_minutes', 30) === $mins)>{{ $text }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="iv-mode-{{ $a->id }}">Type</label>
                        <select class="input" id="iv-mode-{{ $a->id }}" name="mode">
                            @foreach (\App\Models\Interview::MODES as $key => $text)
                                <option value="{{ $key }}" @selected(old('mode', 'video') === $key)>{{ $text }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label" for="iv-details-{{ $a->id }}">Link, phone number or address</label>
                        <input class="input" id="iv-details-{{ $a->id }}" name="details" required maxlength="500" placeholder="https://meet.google.com/..." value="{{ $formOpen ? old('details') : '' }}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label" for="iv-note-{{ $a->id }}">Message to candidate (optional)</label>
                        <input class="input" id="iv-note-{{ $a->id }}" name="note" maxlength="1000" value="{{ $formOpen ? old('note') : '' }}">
                    </div>
                    <div class="sm:col-span-2"><button class="btn-primary" type="submit">Send to candidate</button></div>
                </form>
            </details>
        @endif
    </div>
@endunless