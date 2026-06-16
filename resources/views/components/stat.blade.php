@props(['label' => '', 'value' => '', 'color' => 'brand', 'sub' => null])

@php
    $accent = [
        'brand'   => 'text-brand-600 dark:text-brand-400',
        'green'   => 'text-emerald-600 dark:text-emerald-400',
        'red'     => 'text-red-600 dark:text-red-400',
        'amber'   => 'text-amber-600 dark:text-amber-400',
        'gray'    => 'text-gray-700 dark:text-gray-200',
    ][$color] ?? 'text-brand-600';
@endphp

<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <div class="flex items-center justify-between">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
        @isset($icon)
            <span class="{{ $accent }}">{{ $icon }}</span>
        @endisset
    </div>
    <p class="mt-2 text-2xl font-bold {{ $accent }}">{{ $value }}</p>
    @if ($sub)<p class="mt-1 text-xs text-gray-400">{{ $sub }}</p>@endif
    {{ $slot }}
</div>
