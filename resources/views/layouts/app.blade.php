<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8fafc">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    <script>
        (function () {
            var theme = 'light';

            try {
                theme = localStorage.getItem('theme') === 'dark' ? 'dark' : 'light';
            } catch (error) {
                theme = 'light';
            }

            document.documentElement.classList.toggle('dark', theme === 'dark');
            document.documentElement.style.colorScheme = theme;

            var meta = document.querySelector('meta[name="theme-color"]');
            if (meta) {
                meta.setAttribute('content', theme === 'dark' ? '#060c15' : '#f8fafc');
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full font-sans antialiased" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen">
        <div
            x-show="sidebarOpen"
            x-cloak
            x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm lg:hidden"
            aria-hidden="true"
        ></div>

        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-slate-200 bg-white/95 px-4 py-5 backdrop-blur transition-transform duration-300 lg:translate-x-0 dark:border-white/[0.06] dark:bg-night-950/95"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex items-center justify-between px-2">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-500/10 ring-1 ring-inset ring-emerald-500/25 dark:bg-emerald-400/15 dark:ring-emerald-400/30">
                        <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8m0 0c0-4.418 3.582-8 8-8h1v1c0 4.418-3.582 8-8 8h-1Zm0 0c0-3.866-3.134-7-7-7H4v1c0 3.866 3.134 7 7 7h1Z" />
                        </svg>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-slate-900 dark:text-white">Bahari Farm</span>
                        <span class="block text-[11px] text-slate-500">Greenhouse Monitor</span>
                    </span>
                </a>
                <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden dark:text-slate-400 dark:hover:bg-white/5" @click="sidebarOpen = false" aria-label="Tutup menu">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" />
                    </svg>
                </button>
            </div>

            <div class="mt-8 px-2">
                <p class="eyebrow">Monitoring</p>
                <nav class="mt-3 space-y-1" aria-label="Navigasi utama">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}">
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A1.5 1.5 0 0 1 5.5 4h4A1.5 1.5 0 0 1 11 5.5v4A1.5 1.5 0 0 1 9.5 11h-4A1.5 1.5 0 0 1 4 9.5v-4Zm9 0A1.5 1.5 0 0 1 14.5 4h4A1.5 1.5 0 0 1 20 5.5v4a1.5 1.5 0 0 1-1.5 1.5h-4A1.5 1.5 0 0 1 13 9.5v-4Zm-9 9A1.5 1.5 0 0 1 5.5 13h4a1.5 1.5 0 0 1 1.5 1.5v4A1.5 1.5 0 0 1 9.5 20h-4A1.5 1.5 0 0 1 4 18.5v-4Zm9 0a1.5 1.5 0 0 1 1.5-1.5h4a1.5 1.5 0 0 1 1.5 1.5v4a1.5 1.5 0 0 1-1.5 1.5h-4a1.5 1.5 0 0 1-1.5-1.5v-4Z" />
                        </svg>
                        <span class="flex-1">Dashboard</span>
                        <span class="text-[10px] text-slate-500 dark:text-slate-600">GH1 · GH2</span>
                    </a>
                    <a href="{{ route('nodes.index') }}" class="nav-link {{ request()->routeIs('nodes.*') ? 'nav-link-active' : '' }}">
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9a2 2 0 1 0 4 0 2 2 0 0 0-4 0Zm-3.5 8.5A6.5 6.5 0 0 1 11 13h2a6.5 6.5 0 0 1 6.5 6.5" />
                            <circle cx="12" cy="12" r="9" />
                        </svg>
                        <span class="flex-1">Detail Node</span>
                        <span class="text-[10px] text-slate-500 dark:text-slate-600">5 node</span>
                    </a>
                    <a href="{{ route('history.index') }}" class="nav-link {{ request()->routeIs('history.*') ? 'nav-link-active' : '' }}">
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M7 15l3.5-4 3 2.5L20 7" />
                        </svg>
                        <span class="flex-1">Riwayat &amp; Grafik</span>
                        <span class="text-[10px] text-slate-500 dark:text-slate-600">24h · 7d · 30d</span>
                    </a>
                </nav>
            </div>

            <div class="mt-8 px-2">
                <p class="eyebrow">Topologi LoRa</p>
                <div class="mt-3 space-y-2 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs dark:border-white/[0.06] dark:bg-white/[0.02]">
                    @foreach (config('greenhouse.greenhouses') as $greenhouseId => $greenhouse)
                        <div class="flex items-center justify-between gap-2">
                            <span class="flex items-center gap-2 text-slate-600 dark:text-slate-400">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                                {{ $greenhouseId }}
                            </span>
                            <span class="text-slate-500">{{ count($greenhouse['nodes']) }} node</span>
                        </div>
                    @endforeach
                    <div class="border-t border-slate-200 pt-2 text-[11px] leading-relaxed text-slate-500 dark:border-white/5">
                        5 node Arduino · 10 parameter per node
                    </div>
                </div>
            </div>

            <div class="mt-auto space-y-3 px-2 pt-6">
                <div class="rounded-xl border border-emerald-500/25 bg-emerald-500/[0.06] p-3 dark:border-emerald-400/15 dark:bg-emerald-400/[0.06]">
                    <div class="flex items-center gap-2 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-70 dark:bg-emerald-400"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                        </span>
                        Sistem aktif
                    </div>
                    <p class="mt-1 text-[11px] leading-relaxed text-slate-500">Raspberry Pi 5 · MariaDB · LoRa</p>
                </div>
                <p class="px-1 text-[10px] text-slate-500 dark:text-slate-600">v1.0.0 — Monitoring Multi-Greenhouse</p>
            </div>
        </aside>

        <div class="lg:pl-72">
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/80 backdrop-blur dark:border-white/[0.06] dark:bg-night-950/80">
                <div class="mx-auto flex max-w-[1440px] items-center gap-2 px-4 py-3 sm:gap-3 sm:px-6 lg:px-8">
                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden dark:text-slate-400 dark:hover:bg-white/5" @click="sidebarOpen = true" aria-label="Buka menu">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                    </button>

                    <div class="min-w-0 flex-1">
                        <h1 class="truncate text-base font-semibold text-slate-900 sm:text-lg dark:text-white">@yield('title', 'Dashboard')</h1>
                        <p class="truncate text-[11px] text-slate-500 sm:text-xs">@yield('subtitle', 'Monitoring kondisi greenhouse secara real-time')</p>
                    </div>

                    <div class="hidden items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 sm:flex dark:border-white/[0.06] dark:bg-white/[0.02]">
                        <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" />
                            <path stroke-linecap="round" d="M12 7v5l3 2" />
                        </svg>
                        <div class="leading-tight" x-data="liveClock()" x-init="start()">
                            <p class="font-mono text-sm font-semibold tabular-nums text-slate-800 dark:text-slate-100" x-text="time">--:--:--</p>
                            <p class="text-[10px] text-slate-500" x-text="date">Memuat tanggal…</p>
                        </div>
                    </div>

                    <span class="hidden items-center gap-1.5 rounded-full border border-emerald-500/25 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-700 md:inline-flex dark:border-emerald-400/20 dark:bg-emerald-400/10 dark:text-emerald-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span> Live
                    </span>

                    <button
                        type="button"
                        x-data="themeToggle()"
                        @click="toggle()"
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-100 hover:text-slate-900 dark:border-white/[0.08] dark:bg-white/[0.02] dark:text-slate-400 dark:hover:border-white/20 dark:hover:bg-white/[0.06] dark:hover:text-white"
                        :aria-label="theme === 'dark' ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'"
                        :title="theme === 'dark' ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'"
                        x-bind:aria-pressed="theme === 'dark'"
                    >
                        <svg class="h-4 w-4 dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1 1 11.21 3a7 7 0 0 0 9.79 9.79Z" />
                        </svg>
                        <svg class="hidden h-4 w-4 dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <circle cx="12" cy="12" r="4.5" />
                            <path stroke-linecap="round" d="M12 2.5v2m0 15v2M2.5 12h2m15 0h2M5.3 5.3l1.4 1.4m10.6 10.6 1.4 1.4m0-13.4-1.4 1.4M6.7 17.3l-1.4 1.4" />
                        </svg>
                    </button>
                </div>
            </header>

            <main class="mx-auto max-w-[1440px] px-4 pb-14 pt-6 sm:px-6 lg:px-8">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
