@extends('layouts.app')

@section('title', 'Riwayat & Grafik')
@section('subtitle', 'Telusuri data historis per greenhouse, node, dan parameter')

@section('content')
    <form
        method="GET"
        action="{{ route('history.index') }}"
        class="panel p-4 sm:p-5"
    >
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <label class="block">
                <span class="eyebrow">Greenhouse</span>
                <select name="gh" class="field mt-1.5" onchange="this.form.submit()">
                    @foreach ($greenhouseConfig as $ghId => $greenhouse)
                        <option value="{{ $ghId }}" @selected($filters['gh'] === $ghId)>
                            {{ $greenhouse['name'] }} ({{ count($greenhouse['nodes']) }} node)
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="eyebrow">Sumber Data</span>
                <select name="source" class="field mt-1.5">
                    <option value="avg" @selected($filters['source'] === 'avg')>Rata-rata Greenhouse</option>
                    @foreach ($greenhouseConfig[$filters['gh']]['nodes'] as $nodeId)
                        <option value="{{ $nodeId }}" @selected($filters['source'] === $nodeId)>
                            Node {{ substr($nodeId, 1) }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="eyebrow">Parameter</span>
                <select name="parameter" class="field mt-1.5">
                    @foreach ($parameters as $key => $meta)
                        <option value="{{ $key }}" @selected($filters['parameter'] === $key)>
                            {{ $meta['label'] }} ({{ $meta['unit'] }})
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="eyebrow">Rentang Waktu</span>
                <select name="range" class="field mt-1.5">
                    @foreach ($ranges as $key => $label)
                        <option value="{{ $key }}" @selected($filters['range'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary h-[38px] w-full">Terapkan</button>
                <a href="{{ route('history.index') }}" class="btn-ghost h-[38px]">Reset</a>
            </div>
        </div>

        <p class="mt-3 text-[10px] text-slate-500 dark:text-slate-600">
            Mengganti greenhouse langsung memuat ulang daftar node. Agregasi otomatis:
            24 jam → per jam, 7 hari → per 6 jam, 30 hari → per hari.
            Sumber “Rata-rata Greenhouse” menggabungkan seluruh node pada greenhouse terpilih.
        </p>
    </form>


    @php
        $decimals = $parameters[$chart['parameter']]['decimals'] ?? 2;
        $hasData = collect($chart['values'])->contains(fn ($value) => $value !== null);
        $stats = [
            'min' => 'Min',
            'max' => 'Max',
            'avg' => 'Rata-rata',
            'latest' => 'Terakhir',
        ];
    @endphp

    <div class="panel mt-5 p-4 sm:p-5" x-data="historyChart({{ Js::from($chart) }})" x-init="init()">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="eyebrow">{{ $chart['greenhouse_name'] }} · {{ $chart['source_label'] }}</p>
                <h2 class="mt-1 text-base font-semibold text-slate-900 dark:text-white">
                    {{ $chart['parameter_label'] }}
                    <span class="text-xs font-normal text-slate-500">({{ $chart['unit'] }})</span>
                </h2>
            </div>
            <div class="flex flex-wrap gap-4 sm:gap-6">
                @foreach ($stats as $key => $label)
                    <div class="text-right">
                        <p class="text-[10px] uppercase tracking-wide text-slate-500">{{ $label }}</p>
                        <p class="text-sm font-semibold tabular-nums text-slate-800 dark:text-slate-100">
                            {{ $chart['stats'][$key] === null ? '—' : number_format($chart['stats'][$key], $decimals, ',', '.') }}
                            <span class="text-[10px] font-normal text-slate-500">{{ $chart['unit'] }}</span>
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="relative mt-5 h-72 sm:h-80">
            <canvas x-ref="canvas" role="img" aria-label="Grafik riwayat {{ $chart['parameter_label'] }}"></canvas>
            @unless ($hasData)
                <div class="absolute inset-0 grid place-items-center rounded-xl bg-white/70 text-center dark:bg-night-950/50">
                    <div>
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Belum ada data pada filter ini</p>
                        <p class="mt-1 text-xs text-slate-500">Coba ubah rentang waktu, parameter, atau sumber data.</p>
                    </div>
                </div>
            @endunless
        </div>
    </div>

    <div class="panel mt-5 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-3.5 sm:px-5 dark:border-white/[0.06]">
            <div>
                <h2 class="panel-title">Riwayat Data</h2>
                <p class="text-[11px] text-slate-500">
                    {{ $chart['greenhouse_name'] }} · {{ $chart['source_label'] }} · {{ $chart['parameter_label'] }}
                </p>
            </div>
            <span class="badge bg-slate-100 text-slate-700 ring-slate-200 dark:bg-white/[0.04] dark:text-slate-300 dark:ring-white/10">
                {{ number_format($logs->total(), 0, ',', '.') }} baris
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-[10px] uppercase tracking-[0.14em] text-slate-500 dark:border-white/[0.06] dark:bg-white/[0.02]">
                    <tr>
                        <th class="px-4 py-3 font-semibold sm:px-5">Waktu</th>
                        <th class="px-4 py-3 font-semibold">Greenhouse</th>
                        <th class="px-4 py-3 font-semibold">Node</th>
                        <th class="px-4 py-3 font-semibold">Parameter</th>
                        <th class="px-4 py-3 text-right font-semibold sm:px-5">Nilai</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-white/[0.04]">
                    @forelse ($logs as $log)
                        <tr class="transition-colors hover:bg-slate-50 dark:hover:bg-white/[0.025]">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-500 sm:px-5 dark:text-slate-400">
                                {{ $log->created_at->format('d M Y, H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge bg-slate-100 text-slate-700 ring-slate-200 dark:bg-white/[0.04] dark:text-slate-300 dark:ring-white/10">{{ $log->greenhouse_id }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $log->node_id }}</td>
                            <td class="px-4 py-3 text-slate-600 dark:text-slate-400">{{ $parameters[$log->parameter]['label'] ?? $log->parameter }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums text-slate-900 sm:px-5 dark:text-slate-100">
                                {{ number_format($log->value, $parameters[$log->parameter]['decimals'] ?? 2, ',', '.') }}
                                <span class="ml-1 text-[10px] font-normal text-slate-500">{{ $parameters[$log->parameter]['unit'] ?? '' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">
                                Belum ada data untuk filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-4 py-3 sm:px-5 dark:border-white/[0.06]">
            {{ $logs->links() }}
        </div>
    </div>
@endsection

