@props([
    'label',
    'model',
    'type' => 'text',
    'required' => false,
    'min' => null,
    'max' => null,
    'class' => '',
])

<div class="{{ $class }}">
    <label for="settings-{{ str_replace('.', '-', $model) }}" class="field-label">
        {{ $label }} @if ($required)<span class="text-red-600">*</span>@endif
    </label>
    <input
        id="settings-{{ str_replace('.', '-', $model) }}"
        wire:model="{{ $model }}"
        type="{{ $type }}"
        @if ($required) required @endif
        @if ($min !== null) min="{{ $min }}" @endif
        @if ($max !== null) max="{{ $max }}" @endif
        class="field-control mt-1"
    >
    @error($model) <span class="field-error">{{ $message }}</span> @enderror
</div>
