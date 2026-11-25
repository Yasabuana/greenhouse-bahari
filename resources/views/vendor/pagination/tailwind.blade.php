@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-[11px] text-slate-500">
            Menampilkan
            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $paginator->firstItem() }}</span>
            –
            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $paginator->lastItem() }}</span>
            dari
            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ number_format($paginator->total(), 0, ',', '.') }}</span>
            data
        </p>

        <div class="flex flex-wrap items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-8 min-w-8 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 px-2 text-xs text-slate-300 dark:border-white/[0.06] dark:text-slate-600" aria-disabled="true">
                    &lsaquo;
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-slate-200 px-2 text-xs text-slate-600 transition hover:border-emerald-500/40 hover:text-emerald-700 dark:border-white/10 dark:text-slate-300 dark:hover:border-emerald-400/30 dark:hover:text-emerald-300" aria-label="Halaman sebelumnya">
                    &lsaquo;
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="inline-flex h-8 min-w-8 items-center justify-center px-2 text-xs text-slate-400 dark:text-slate-600">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg bg-emerald-500/15 px-2 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-500/25 dark:bg-emerald-400/15 dark:text-emerald-300 dark:ring-emerald-400/25">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-slate-200 px-2 text-xs text-slate-600 transition hover:border-emerald-500/40 hover:text-emerald-700 dark:border-white/10 dark:text-slate-300 dark:hover:border-emerald-400/30 dark:hover:text-emerald-300" aria-label="Ke halaman {{ $page }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg border border-slate-200 px-2 text-xs text-slate-600 transition hover:border-emerald-500/40 hover:text-emerald-700 dark:border-white/10 dark:text-slate-300 dark:hover:border-emerald-400/30 dark:hover:text-emerald-300" aria-label="Halaman berikutnya">
                    &rsaquo;
                </a>
            @else
                <span class="inline-flex h-8 min-w-8 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 px-2 text-xs text-slate-300 dark:border-white/[0.06] dark:text-slate-600" aria-disabled="true">
                    &rsaquo;
                </span>
            @endif
        </div>
    </nav>
@endif
