{{-- Application progress stepper. <x-tracker :status="$application->status" /> --}}
@props(['status'])
@use('App\Enums\ApplicationStatus')
@php
    $pipeline = ApplicationStatus::pipeline();
    $closed = in_array($status, [ApplicationStatus::Rejected, ApplicationStatus::Withdrawn], true);
    $index = array_search($status, $pipeline, true);
@endphp
@if ($closed)
    <p class="flex items-center gap-2 text-sm font-medium {{ $status === ApplicationStatus::Rejected ? 'text-red-700' : 'text-ink-600' }}">
        <x-status-badge :status="$status"/> This application is closed.
    </p>
@else
    <ol class="flex items-start justify-between gap-1" aria-label="Application progress">
        @foreach ($pipeline as $i => $step)
            @php $state = $i < $index ? 'step-done' : ($i === $index ? 'step-now' : ''); @endphp
            <li class="flex min-w-0 flex-1 flex-col items-center text-center {{ $state }}" @if ($i === $index) aria-current="step" @endif>
                <span class="step-dot">@if ($i < $index)<x-icon name="check" class="h-4 w-4"/>@else{{ $i + 1 }}@endif</span>
                <span class="mt-1 hidden text-[11px] font-medium leading-tight text-ink-600 sm:block">{{ $step->label() }}</span>
            </li>
        @endforeach
    </ol>
    <p class="mt-2 text-center text-sm font-semibold text-ink-900 sm:hidden">{{ $status->label() }}</p>
@endif
