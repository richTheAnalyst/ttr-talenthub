{{-- Text-like input with label, hint and validation error. Usage: <x-field name="email" label="Email" type="email" :value="$user->email" required /> --}}
@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false, 'autocomplete' => null, 'inputmode' => null])
@php $id = 'f-'.str_replace(['.', '[', ']'], '-', $name); @endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}@if ($required)<span class="text-red-600" aria-hidden="true"> *</span>@endif</label>
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
           value="{{ $type === 'password' ? '' : old($name, $value) }}"
           @if ($required) required @endif
           @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
           @if ($inputmode) inputmode="{{ $inputmode }}" @endif
           @if ($hint) aria-describedby="{{ $id }}-hint" @endif
           {{ $attributes->except('class')->merge(['class' => 'input'.($errors->has($name) ? ' border-red-400' : '')]) }}>
    @if ($hint)<p id="{{ $id }}-hint" class="hint">{{ $hint }}</p>@endif
    @error($name)<p class="error">{{ $message }}</p>@enderror
</div>
