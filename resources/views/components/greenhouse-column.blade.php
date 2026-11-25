@props(['ghId', 'name', 'location', 'nodeCount', 'onlineCount' => null, 'parameters'])

@php
    $airParameters = array_filter($parameters, fn ($meta) => $meta['group'] === 'air');
    $soilParameters = array_filter($parameters, fn ($meta) => $meta['group'] === 'soil');
@endphp

<section class="panel overflow-hidden">
    <header class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-200 bg-slate-50/80 px-4 py-4 sm:px-5 dark:border-white/[0.06] dark:bg-white/[0.015]">
        <div class="flex items-center gap-3">
            <div class="grid h-11 w-11 place-items-center rounded-xl bg-emerald-500/10 text-xs font-bold tracking-wide text-emerald-700 ring-1 ring-inset ring-emerald-500/20 dark:bg-emerald-400/10 dark:text-emerald-300 dark:ring-emerald-400/20">
                {{ $ghId }}
            </div>
            <div>
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ $name }}</h2>
                <p class="text-[11px] text-slate-500">{{ $location }} · {{ $nodeCount }} node Arduino</p>
            </div>
        </div>
        <div class="text-right">
            <span class="badge" :class="onlineBadgeClass('{{ $ghId }}')">
                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                <span x-text="nodeSummary('{{ $ghId }}')">{{ $onlineCount ?? $nodeCount }}/{{ $nodeCount }} Node Online</span>
            </span>
            <p class="mt-1.5 text-[10px] text-slate-500">
                Update: <span class="text-slate-600 dark:text-slate-400" x-text="lastSeen('{{ $ghId }}')">—</span>
            </p>
        </div>
    </header>

    <div class="space-y-5 p-4 transition-opacity duration-500 sm:p-5" :class="flash ? 'opacity-60' : 'opacity-100'">
        <div>
            <div class="mb-2.5 flex items-center justify-between">
                <h3 class="panel-title">Kondisi Udara <span class="ml-1 text-[10px] font-medium uppercase tracking-wide text-slate-500">SHT31</span></h3>
                <span class="text-[10px] text-slate-500 dark:text-slate-600">rata-rata node online</span>
            </div>
            <div class="grid grid-cols-2 gap-3">
                @foreach ($airParameters as $key => $meta)
                    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-white to-slate-50 p-3.5 shadow-sm shadow-slate-200/50 dark:border-white/[0.06] dark:from-white/[0.05] dark:to-transparent dark:shadow-none">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ $meta['label'] }}</p>
                            <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[9px] font-semibold text-slate-500 dark:bg-white/[0.06] dark:text-slate-400">{{ $meta['icon'] }}</span>
                        </div>
                        <p class="mt-2 flex items-baseline gap-1.5">
                            <span class="text-3xl font-semibold tracking-tight text-slate-900 tabular-nums dark:text-white" x-text="value('{{ $ghId }}', '{{ $key }}', {{ $meta['decimals'] }})">—</span>
                            <span class="text-xs text-slate-500">{{ $meta['unit'] }}</span>
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <div class="mb-2.5 flex items-center justify-between">
                <h3 class="panel-title">Kondisi Media Tanam <span class="ml-1 text-[10px] font-medium uppercase tracking-wide text-slate-500">NPK 8-in-1</span></h3>
            </div>
            <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-4">
                @foreach ($soilParameters as $key => $meta)
                    <div class="metric">
                        <div class="flex items-center justify-between gap-1">
                            <p class="truncate text-[10px] font-medium uppercase tracking-wide text-slate-500">{{ $meta['label'] }}</p>
                            <span class="text-[9px] font-semibold text-slate-500 dark:text-slate-600">{{ $meta['icon'] }}</span>
                        </div>
                        <p class="mt-1.5 flex items-baseline gap-1">
                            <span class="text-lg font-semibold tabular-nums text-slate-800 dark:text-slate-100" x-text="value('{{ $ghId }}', '{{ $key }}', {{ $meta['decimals'] }})">—</span>
                            <span class="text-[10px] text-slate-500">{{ $meta['unit'] }}</span>
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-between border-t border-slate-200 pt-3 text-[10px] text-slate-500 dark:border-white/[0.05] dark:text-slate-600">
            <span>{{ $nodeCount }} node · 10 parameter</span>
            <a href="{{ route('nodes.index') }}" class="inline-flex items-center gap-1 text-slate-600 transition hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-300">
                Detail node
                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>
</section>
