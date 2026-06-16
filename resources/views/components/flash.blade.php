@php
    $types = [
        'success' => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/30', 'text' => 'text-emerald-800 dark:text-emerald-200', 'ring' => 'ring-emerald-200 dark:ring-emerald-800'],
        'error'   => ['bg' => 'bg-red-50 dark:bg-red-900/30', 'text' => 'text-red-800 dark:text-red-200', 'ring' => 'ring-red-200 dark:ring-red-800'],
        'warning' => ['bg' => 'bg-amber-50 dark:bg-amber-900/30', 'text' => 'text-amber-800 dark:text-amber-200', 'ring' => 'ring-amber-200 dark:ring-amber-800'],
        'info'    => ['bg' => 'bg-brand-50 dark:bg-brand-900/30', 'text' => 'text-brand-800 dark:text-brand-200', 'ring' => 'ring-brand-200 dark:ring-brand-800'],
    ];
@endphp

@foreach ($types as $type => $c)
    @if (session()->has($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition
             class="mb-4 flex items-start gap-3 rounded-lg px-4 py-3 text-sm font-medium ring-1 {{ $c['bg'] }} {{ $c['text'] }} {{ $c['ring'] }}">
            <span class="flex-1">{{ session($type) }}</span>
            <button type="button" @click="show = false" class="opacity-60 hover:opacity-100">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif
@endforeach
