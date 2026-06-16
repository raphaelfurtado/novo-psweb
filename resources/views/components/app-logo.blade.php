<a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5">
    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white shadow">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21"/></svg>
    </span>
    <span class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">{{ config('app.name') }}</span>
</a>
