<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Entrar' }} · {{ config('app.name') }}</title>
    <script>
        (function () {
            const t = localStorage.getItem('theme');
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-gray-100 antialiased dark:bg-gray-950">
    <div class="flex min-h-full flex-col items-center justify-center px-4 py-10">
        <div class="mb-8 flex flex-col items-center text-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-lg">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
            </div>
            <h1 class="mt-4 text-2xl font-bold text-gray-900 dark:text-white">{{ config('app.name') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Gestão financeira do condomínio</p>
        </div>

        <div class="w-full max-w-md">
            {{ $slot }}
        </div>

        <p class="mt-8 text-xs text-gray-400">&copy; {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
    @livewireScripts
</body>
</html>
