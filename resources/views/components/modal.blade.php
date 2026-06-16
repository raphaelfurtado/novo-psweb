@props(['model', 'title' => null, 'maxWidth' => 'lg'])

@php
    $mw = [
        'sm' => 'max-w-sm', 'md' => 'max-w-md', 'lg' => 'max-w-lg',
        'xl' => 'max-w-xl', '2xl' => 'max-w-2xl', '3xl' => 'max-w-3xl',
    ][$maxWidth] ?? 'max-w-lg';
@endphp

<div x-data="{ show: $wire.entangle('{{ $model }}') }">
    <template x-teleport="body">
        <div x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
             @keydown.escape.window="show = false">
            <div x-show="show" x-transition.opacity class="fixed inset-0 bg-gray-900/50"
                 @click="show = false"></div>

            <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
                <div x-show="show"
                     x-transition:enter="ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                     class="relative w-full {{ $mw }} rounded-xl bg-white shadow-xl dark:bg-gray-800">
                    @if ($title)
                        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
                            <button type="button" @click="show = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    @endif
                    <div class="p-5">{{ $slot }}</div>
                </div>
            </div>
        </div>
    </template>
</div>
