@extends('layouts.app')

@section('title', 'Detail Node')
@section('subtitle', 'Status koneksi dan pembacaan sensor tiap node LoRa')

@section('content')
    <div
        x-data="nodesPage({{ Js::from($nodes) }}, '{{ route('api.nodes') }}', 'GH1')"
        x-init="start()"
    >
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1 dark:border-white/[0.08] dark:bg-night-900/80">
                @foreach ($greenhouseConfig as $ghId => $greenhouse)
                    <button
                        type="button"
                        @click="tab = '{{ $ghId }}'"
                        :class="tab === '{{ $ghId }}' ? 'bg-emerald-500/15 text-emerald-700 shadow-sm dark:bg-emerald-400/15 dark:text-emerald-300' : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200'"
                        class="rounded-lg px-3.5 py-2 text-xs font-semibold transition"
                    >
                        {{ $greenhouse['name'] }}
                        <span class="ml-1 text-[10px] font-normal opacity-70">{{ count($greenhouse['nodes']) }} node</span>
                    </button>
                @endforeach
            </div>

            <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                <span>Status online: data &lt; {{ $onlineThreshold }} menit</span>
                <span class="text-slate-300 dark:text-slate-700">·</span>
                <span>Refresh 15 detik</span>
                <span class="text-slate-300 dark:text-slate-700">·</span>
                <span>Terakhir: <span class="font-mono text-slate-700 dark:text-slate-300" x-text="lastUpdated">—</span></span>
                <button type="button" class="btn-ghost ml-1" @click="refresh()" :disabled="refreshing">
                    <svg class="h-3 w-3" :class="refreshing ? 'animate-spin' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 11a8.1 8.1 0 0 0-14.6-3M4 5v4h4m-4 4a8.1 8.1 0 0 0 14.6 3M20 19v-4h-4" />
                    </svg>
                </button>
            </div>
        </div>

        <div
            x-show="error"
            x-cloak
            class="mb-4 rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-xs text-rose-700 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200"
            x-text="error"
        ></div>

        @php
            $airParameters = array_filter($parameters, fn ($meta) => $meta['group'] === 'air');
            $soilParameters = array_filter($parameters, fn ($meta) => $meta['group'] === 'soil');
        @endphp

        @foreach ($greenhouseConfig as $ghId => $greenhouse)
            <div
                x-show="tab === '{{ $ghId }}'"
                x-cloak
                class="grid grid-cols-1 gap-4 {{ count($greenhouse['nodes']) === 3 ? 'lg:grid-cols-3' : 'md:grid-cols-2' }}"
            >
                @foreach ($nodes[$ghId] as $node)
                    <article class="panel overflow-hidden">
                        <header class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-3.5 dark:border-white/[0.06]">
                            <div class="flex items-center gap-2.5">
                                <span class="grid h-9 w-9 place-items-center rounded-lg bg-slate-100 text-[11px] font-bold text-slate-600 ring-1 ring-inset ring-slate-200 dark:bg-white/[0.04] dark:text-slate-300 dark:ring-white/10">
                                    {{ $node['id'] }}
                                </span>
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $node['label'] }}</h2>
                                    <p class="text-[10px] text-slate-500">LoRa · {{ $ghId }}</p>
                                </div>
                            </div>
                            <span class="badge" :class="statusClass('{{ $ghId }}', '{{ $node['id'] }}')">
                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                <span x-text="statusLabel('{{ $ghId }}', '{{ $node['id'] }}')">{{ $node['online'] ? 'Online' : 'Offline' }}</span>
                            </span>
                        </header>

                        <div class="px-4 pt-3 text-[10px] text-slate-500">
                            Terakhir masuk:
                            <span class="text-slate-700 dark:text-slate-300" x-text="lastSeen('{{ $ghId }}', '{{ $node['id'] }}')">{{ $node['last_seen_label'] }}</span>
                        </div>

                        <div class="p-4">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="eyebrow">Kondisi Udara</p>
                                <span class="text-[9px] text-slate-500 dark:text-slate-600">SHT31</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2.5">
                                @foreach ($airParameters as $key => $meta)
                                    <div class="rounded-xl border border-emerald-500/20 bg-emerald-50 px-3 py-2.5 dark:border-emerald-400/10 dark:bg-emerald-400/[0.04]">
                                        <p class="text-[10px] uppercase tracking-wide text-emerald-700/80 dark:text-emerald-200/60">{{ $meta['label'] }}</p>
                                        <p class="mt-1 flex items-baseline gap-1">
                                            <span class="text-xl font-semibold tabular-nums text-slate-900 dark:text-white" x-text="value('{{ $ghId }}', '{{ $node['id'] }}', '{{ $key }}', {{ $meta['decimals'] }})">—</span>
                                            <span class="text-[10px] text-slate-500">{{ $meta['unit'] }}</span>
                                        </p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mb-2 mt-4 flex items-center justify-between">
                                <p class="eyebrow">Media Tanam</p>
                                <span class="text-[9px] text-slate-500 dark:text-slate-600">NPK 8-in-1</span>
                            </div>
                            <div class="space-y-1">
                                @foreach ($soilParameters as $key => $meta)
                                    <div class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 odd:bg-slate-50 dark:odd:bg-white/[0.02]">
                                        <span class="text-[11px] text-slate-600 dark:text-slate-400">{{ $meta['label'] }}</span>
                                        <span class="text-xs font-semibold tabular-nums text-slate-800 dark:text-slate-200">
                                            <span x-text="value('{{ $ghId }}', '{{ $node['id'] }}', '{{ $key }}', {{ $meta['decimals'] }})">—</span>
                                            <span class="ml-0.5 text-[9px] font-normal text-slate-500">{{ $meta['unit'] }}</span>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endforeach

        <p class="mt-5 text-[10px] text-slate-500 dark:text-slate-600">
            Data ditampilkan dari batch terakhir setiap node. Node tanpa data dalam {{ $onlineThreshold }} menit ditandai offline dan tidak diikutsertakan pada rata-rata dashboard.
        </p>
    </div>
@endsection

