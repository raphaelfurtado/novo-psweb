<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>
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
<body class="h-full bg-gray-100 text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100"
      x-data="{ sidebarOpen: false }">

    <!-- Drawer mobile -->
    <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 lg:hidden">
        <div x-show="sidebarOpen" x-transition.opacity class="absolute inset-0 bg-gray-900/50"
             @click="sidebarOpen = false"></div>
        <div x-show="sidebarOpen" x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
             class="relative flex h-full w-72 flex-col bg-white shadow-xl dark:bg-gray-900">
            <div class="flex h-16 items-center gap-2 border-b border-gray-200 px-4 dark:border-gray-700">
                <x-app-logo />
            </div>
            @include('partials.nav')
        </div>
    </div>

    <!-- Sidebar desktop -->
    <aside class="hidden lg:fixed lg:inset-y-0 lg:z-30 lg:flex lg:w-72 lg:flex-col">
        <div class="flex h-full flex-col border-r border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="flex h-16 items-center gap-2 border-b border-gray-200 px-5 dark:border-gray-700">
                <x-app-logo />
            </div>
            @include('partials.nav')
        </div>
    </aside>

    <!-- Conteúdo -->
    <div class="lg:pl-72">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-gray-200 bg-white/80 px-4 backdrop-blur dark:border-gray-700 dark:bg-gray-900/80 sm:px-6">
            <button type="button" class="btn-ghost -ml-2 p-2 lg:hidden" @click="sidebarOpen = true">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>

            <div class="flex-1 text-sm font-semibold text-gray-700 dark:text-gray-200">
                {{ $header ?? '' }}
            </div>

            <!-- Toggle tema -->
            <button type="button" class="btn-ghost p-2"
                    x-data="{ dark: document.documentElement.classList.contains('dark') }"
                    @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('theme', dark ? 'dark' : 'light')"
                    :title="dark ? 'Modo claro' : 'Modo escuro'">
                <svg x-show="!dark" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/></svg>
                <svg x-show="dark" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/></svg>
            </button>

            <!-- Menu do usuário -->
            <div class="relative" x-data="{ open: false }" @keydown.escape="open = false">
                <button type="button" class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-gray-100 dark:hover:bg-gray-800" @click="open = !open">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white">
                        {{ mb_strtoupper(mb_substr(auth()->user()->nome ?? '?', 0, 1)) }}
                    </span>
                    <span class="hidden text-sm font-medium sm:block">{{ auth()->user()->primeiro_nome }}</span>
                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                </button>
                <div x-show="open" x-cloak x-transition @click.outside="open = false"
                     class="absolute right-0 mt-2 w-56 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-100 px-4 py-2 dark:border-gray-700">
                        <p class="truncate text-sm font-medium">{{ auth()->user()->nome }}</p>
                        <p class="text-xs text-gray-500">{{ auth()->user()->isAdmin() ? 'Administrador' : 'Morador' }}</p>
                    </div>
                    <a href="{{ route('senha.edit') }}" wire:navigate class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Trocar senha</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">Sair</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            <x-flash />
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
</body>
</html>
