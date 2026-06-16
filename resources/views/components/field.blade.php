@props(['label' => null, 'name' => null, 'hint' => null, 'required' => false])

<div {{ $attributes->only('class') }}>
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif class="form-label">
            {{ $label }}
            @if ($required)<span class="text-red-500">*</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint && (! $name || ! $errors->has($name)))
        <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
    @endif

    @if ($name)
        @error($name)
            <p class="mt-1 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    @endif
</div>
