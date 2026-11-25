@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan kondisi terkini seluruh greenhouse')

@section('content')
    <div
        x-data="dashboardPage({{ Js::from($greenhouses) }}, '{{ route('api.dashboard') }}')"
        x-init="start()"
    >
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-70 dark:bg-emerald-400"></span>
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                </span>
                <span>Auto-refresh 10 detik</span>
                <span class="text-slate-300 dark:text-slate-700">·</span>
                <span>Terakhir: <span class="font-mono text-slate-700 dark:text-slate-300" x-text="lastUpdated">—</span></span>
                <span class="text-slate-300 dark:text-slate-700">·</span>
                <span>Ambang online {{ $onlineThreshold }} menit</span>
            </div>

            <button type="button" class="btn-ghost" @click="refresh()" :disabled="refreshing">
                <svg class="h-3.5 w-3.5" :class="refreshing ? 'animate-spin' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 11a8.1 8.1 0 0 0-14.6-3M4 5v4h4m-4 4a8.1 8.1 0 0 0 14.6 3M20 19v-4h-4" />
                </svg>
                <span x-text="refreshing ? 'Memuat…' : 'Refresh'">Refresh</span>
            </button>
        </div>

        <div
            x-show="error"
            x-cloak
            class="mb-5 flex items-start gap-2 rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-xs text-rose-700 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200"
        >
            <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <circle cx="12" cy="12" r="9" />
                <path stroke-linecap="round" d="M12 8v4m0 4h.01" />
            </svg>
            <span x-text="error"></span>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            @foreach ($greenhouseConfig as $ghId => $greenhouse)
                <x-greenhouse-column
                    :gh-id="$ghId"
                    :name="$greenhouse['name']"
                    :location="$greenhouse['location']"
                    :node-count="count($greenhouse['nodes'])"
                    :online-count="$greenhouses[$ghId]['nodes_online'] ?? 0"
                    :parameters="$parameters"
                />
            @endforeach
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-[10px] text-slate-500 dark:border-white/[0.05] dark:bg-white/[0.015] dark:text-slate-600">
            <span>2 greenhouse · 5 node LoRa · 10 parameter sensor (NPK 8-in-1 + SHT31)</span>
            <a href="{{ route('history.index') }}" class="inline-flex items-center gap-1 text-slate-600 transition hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-300">
                Lihat riwayat &amp; grafik
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>
@endsection
