@props(['title' => '', 'subtitle' => null])

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-2xl">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>@endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
