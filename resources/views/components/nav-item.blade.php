@props(['href' => '#', 'active' => false])

<a href="{{ $href }}" wire:navigate
   @class([
       'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
       'bg-brand-600 text-white shadow-sm' => $active,
       'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700/60 dark:hover:text-white' => ! $active,
   ])>
    {{ $slot }}
</a>
