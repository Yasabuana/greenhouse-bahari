import './bootstrap';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

const formatNumber = (value, decimals = 1) => {
    if (value === null || value === undefined || value === '' || Number.isNaN(Number(value))) {
        return '—';
    }

    return Number(value).toFixed(decimals);
};

const pad = (value) => String(value).padStart(2, '0');

const THEME_STORAGE_KEY = 'theme';
const THEME_COLORS = {
    light: {
        meta: '#f8fafc',
        grid: 'rgba(100, 116, 139, 0.16)',
        tick: '#475569',
        tooltipBg: '#ffffff',
        tooltipBorder: 'rgba(15, 23, 42, 0.12)',
        tooltipTitle: '#0f172a',
        tooltipBody: '#475569',
        line: '#10b981',
        area: 'rgba(16, 185, 129, 0.26)',
        pointBorder: '#ffffff',
    },
    dark: {
        meta: '#060c15',
        grid: 'rgba(148, 163, 184, 0.08)',
        tick: '#64748b',
        tooltipBg: '#0a1421',
        tooltipBorder: 'rgba(255, 255, 255, 0.1)',
        tooltipTitle: '#e2e8f0',
        tooltipBody: '#94a3b8',
        line: '#34d399',
        area: 'rgba(52, 211, 153, 0.32)',
        pointBorder: '#060c15',
    },
};

const currentTheme = () => (document.documentElement.classList.contains('dark') ? 'dark' : 'light');

const themePalette = (theme = currentTheme()) => THEME_COLORS[theme] ?? THEME_COLORS.light;

const applyTheme = (theme) => {
    const resolved = theme === 'dark' ? 'dark' : 'light';
    const isDark = resolved === 'dark';

    document.documentElement.classList.toggle('dark', isDark);
    document.documentElement.style.colorScheme = resolved;

    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', themePalette(resolved).meta);

    try {
        localStorage.setItem(THEME_STORAGE_KEY, resolved);
    } catch (error) {
        // localStorage tidak tersedia (mode privat / diblokir browser).
    }

    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: resolved } }));

    return resolved;
};

Alpine.data('themeToggle', () => ({
    theme: currentTheme(),

    init() {
        this.theme = currentTheme();
    },

    toggle() {
        this.theme = applyTheme(this.theme === 'dark' ? 'light' : 'dark');
    },
}));

Alpine.data('liveClock', () => ({
    time: '--:--:--',
    date: 'Memuat tanggal…',
    timer: null,

    start() {
        this.tick();
        this.timer = setInterval(() => this.tick(), 1000);
    },

    tick() {
        const now = new Date();
        this.time = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
        this.date = now.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    },

    destroy() {
        clearInterval(this.timer);
    },
}));

Alpine.data('dashboardPage', (initial, endpoint) => ({
    greenhouses: initial,
    endpoint,
    refreshing: false,
    flash: false,
    error: null,
    lastUpdated: '—',
    timer: null,

    start() {
        this.timer = setInterval(() => this.refresh(), 10000);
    },

    destroy() {
        clearInterval(this.timer);
    },

    async refresh() {
        if (this.refreshing) {
            return;
        }

        this.refreshing = true;
        this.error = null;

        try {
            const response = await fetch(this.endpoint, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error('Gagal memuat data dashboard.');
            }

            const data = await response.json();
            this.greenhouses = data.greenhouses;
            this.lastUpdated = data.updated_at_label;
            this.flash = true;

            setTimeout(() => {
                this.flash = false;
            }, 700);
        } catch (error) {
            this.error = error.message;
        } finally {
            this.refreshing = false;
        }
    },

    fmt(value, decimals = 1) {
        return formatNumber(value, decimals);
    },

    value(greenhouseId, parameter, decimals = 1) {
        return this.fmt(this.greenhouses?.[greenhouseId]?.averages?.[parameter], decimals);
    },

    nodeSummary(greenhouseId) {
        const greenhouse = this.greenhouses?.[greenhouseId];

        return greenhouse
            ? `${greenhouse.nodes_online}/${greenhouse.nodes_total} Node Online`
            : '0/0 Node Online';
    },

    lastSeen(greenhouseId) {
        return this.greenhouses?.[greenhouseId]?.last_seen_label ?? 'Belum ada data';
    },

    onlineBadgeClass(greenhouseId) {
        const greenhouse = this.greenhouses?.[greenhouseId];

        if (!greenhouse || greenhouse.nodes_online === 0) {
            return 'bg-rose-500/10 text-rose-700 ring-rose-500/20 dark:text-rose-300 dark:ring-rose-400/20';
        }

        if (greenhouse.nodes_online < greenhouse.nodes_total) {
            return 'bg-amber-400/15 text-amber-700 ring-amber-500/25 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20';
        }

        return 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300 dark:ring-emerald-400/20';
    },
}));

Alpine.data('nodesPage', (initial, endpoint, defaultTab = 'GH1') => ({
    nodes: initial,
    endpoint,
    tab: defaultTab,
    refreshing: false,
    error: null,
    lastUpdated: '—',
    timer: null,

    start() {
        this.timer = setInterval(() => this.refresh(), 15000);
    },

    destroy() {
        clearInterval(this.timer);
    },

    async refresh() {
        if (this.refreshing) {
            return;
        }

        this.refreshing = true;
        this.error = null;

        try {
            const response = await fetch(this.endpoint, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error('Gagal memuat status node.');
            }

            const data = await response.json();
            this.nodes = data.nodes;
            this.lastUpdated = data.updated_at_label;
        } catch (error) {
            this.error = error.message;
        } finally {
            this.refreshing = false;
        }
    },

    node(greenhouseId, nodeId) {
        return (this.nodes?.[greenhouseId] ?? []).find((node) => node.id === nodeId) ?? null;
    },

    isOnline(greenhouseId, nodeId) {
        return Boolean(this.node(greenhouseId, nodeId)?.online);
    },

    statusLabel(greenhouseId, nodeId) {
        return this.isOnline(greenhouseId, nodeId) ? 'Online' : 'Offline';
    },

    statusClass(greenhouseId, nodeId) {
        return this.isOnline(greenhouseId, nodeId)
            ? 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300 dark:ring-emerald-400/20'
            : 'bg-slate-500/10 text-slate-600 ring-slate-400/20 dark:text-slate-400 dark:ring-slate-400/20';
    },

    lastSeen(greenhouseId, nodeId) {
        return this.node(greenhouseId, nodeId)?.last_seen_label ?? 'Belum ada data';
    },

    value(greenhouseId, nodeId, parameter, decimals = 1) {
        return formatNumber(this.node(greenhouseId, nodeId)?.values?.[parameter], decimals);
    },
}));

Alpine.data('historyChart', (data) => ({
    chart: null,
    themeListener: null,

    init() {
        const canvas = this.$refs.canvas;

        if (!canvas) {
            return;
        }

        const palette = themePalette();
        const context = canvas.getContext('2d');
        const gradient = this.areaGradient(context);

        this.chart = new Chart(context, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: `${data.parameter_label} (${data.unit})`,
                        data: data.values,
                        borderColor: palette.line,
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.35,
                        spanGaps: true,
                        borderWidth: 2,
                        pointRadius: 2,
                        pointHoverRadius: 5,
                        pointBackgroundColor: palette.line,
                        pointBorderColor: palette.pointBorder,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: palette.tooltipBg,
                        borderColor: palette.tooltipBorder,
                        borderWidth: 1,
                        titleColor: palette.tooltipTitle,
                        bodyColor: palette.tooltipBody,
                        padding: 10,
                        displayColors: false,
                        callbacks: {
                            label: (context) => {
                                const value = context.parsed.y;

                                return value === null
                                    ? 'Tidak ada data'
                                    : `${data.parameter_label}: ${Number(value).toFixed(2)} ${data.unit}`;
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { color: palette.grid },
                        border: { display: false },
                        ticks: {
                            color: palette.tick,
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 10,
                        },
                    },
                    y: {
                        grid: { color: palette.grid },
                        border: { display: false },
                        ticks: {
                            color: palette.tick,
                            callback: (value) => `${value} ${data.unit}`,
                        },
                    },
                },
            },
        });

        this.themeListener = () => this.syncTheme();
        window.addEventListener('theme-changed', this.themeListener);
    },

    areaGradient(context = this.chart?.ctx ?? this.$refs.canvas?.getContext('2d')) {
        if (!context) {
            return 'transparent';
        }

        const gradient = context.createLinearGradient(0, 0, 0, 340);
        gradient.addColorStop(0, themePalette().area);
        gradient.addColorStop(1, 'rgba(0, 0, 0, 0)');

        return gradient;
    },

    syncTheme() {
        if (!this.chart) {
            return;
        }

        const palette = themePalette();
        const scales = this.chart.options.scales;
        const tooltip = this.chart.options.plugins.tooltip;
        const dataset = this.chart.data.datasets[0];

        scales.x.grid.color = palette.grid;
        scales.x.ticks.color = palette.tick;
        scales.y.grid.color = palette.grid;
        scales.y.ticks.color = palette.tick;

        tooltip.backgroundColor = palette.tooltipBg;
        tooltip.borderColor = palette.tooltipBorder;
        tooltip.titleColor = palette.tooltipTitle;
        tooltip.bodyColor = palette.tooltipBody;

        dataset.borderColor = palette.line;
        dataset.pointBackgroundColor = palette.line;
        dataset.pointBorderColor = palette.pointBorder;
        dataset.backgroundColor = this.areaGradient();

        this.chart.update('none');
    },

    destroy() {
        if (this.themeListener) {
            window.removeEventListener('theme-changed', this.themeListener);
            this.themeListener = null;
        }

        this.chart?.destroy();
    },
}));

Alpine.start();

