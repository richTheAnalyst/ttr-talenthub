@props(['name', 'checked' => false, 'value' => '1'])
@php $id = 'f-'.$name; @endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="flex cursor-pointer items-start gap-3 text-sm text-ink-700">
        <input type="hidden" name="{{ $name }}" value="0">
        <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="{{ $value }}" class="check" @checked(old($name, $checked))>
        <span>{{ $slot }}</span>
    </label>
    @error($name)<p class="error">{{ $message }}</p>@enderror
</div>
