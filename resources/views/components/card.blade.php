@props(['title' => null, 'subtitle' => null, 'padding' => true])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-700">
            <div>
                @if ($title)<h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>@endif
                @if ($subtitle)<p class="text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>@endif
            </div>
            @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div @class(['p-5' => $padding])>
        {{ $slot }}
    </div>
</div>
