@props(['name', 'label' => null, 'value' => null, 'hint' => null, 'required' => false, 'rows' => 4])
@php $id = 'f-'.str_replace(['.', '[', ']'], '-', $name); @endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }}@if ($required)<span class="text-red-600" aria-hidden="true"> *</span>@endif</label>
    @endif
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @if ($required) required @endif
              {{ $attributes->except('class')->merge(['class' => 'input'.($errors->has($name) ? ' border-red-400' : '')]) }}>{{ old($name, $value) }}</textarea>
    @if ($hint)<p class="hint">{{ $hint }}</p>@endif
    @error($name)<p class="error">{{ $message }}</p>@enderror
</div>
