{{-- <x-select name="job_type" label="Type" :options="$types" :value="$job->job_type" placeholder="Any" /> --}}
@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'required' => false, 'hint' => null])
@php
    $id = 'f-'.str_replace(['.', '[', ']'], '-', $name);
    $current = (string) old($name, $value instanceof \BackedEnum ? $value->value : $value);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}@if ($required)<span class="text-red-600" aria-hidden="true"> *</span>@endif</label>
    @endif
    <select id="{{ $id }}" name="{{ $name }}" @if ($required) required @endif
            {{ $attributes->except('class')->merge(['class' => 'input'.($errors->has($name) ? ' border-red-400' : '')]) }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $key => $text)
            <option value="{{ $key }}" @selected($current !== '' && (string) $key === $current)>{{ $text }}</option>
        @endforeach
    </select>
    @if ($hint)<p class="hint">{{ $hint }}</p>@endif
    @error($name)<p class="error">{{ $message }}</p>@enderror
</div>
