<?php

namespace App\Services;

use App\Models\SensorLog;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class SensorDataService
{
    /**
     * Seluruh konfigurasi greenhouse (GH1/GH2 beserta node-nya).
     *
     * @return array<string, array{name: string, location: string, nodes: list<string>}>
     */
    public function greenhouseConfig(): array
    {
        return config('greenhouse.greenhouses', []);
    }

    /**
     * Metadata sepuluh parameter sensor.
     *
     * @return array<string, array<string, mixed>>
     */
    public function parameterConfig(): array
    {
        return config('greenhouse.parameters', []);
    }

    public function onlineThresholdMinutes(): int
    {
        return (int) config('greenhouse.online_threshold_minutes', 15);
    }

    /**
     * Ringkasan semua greenhouse untuk dashboard.
     *
     * @return array<string, array<string, mixed>>
     */
    public function overview(): array
    {
        $overview = [];

        foreach (array_keys($this->greenhouseConfig()) as $greenhouseId) {
            $overview[$greenhouseId] = $this->greenhouseOverview($greenhouseId);
        }

        return $overview;
    }

    /**
     * Ringkasan satu greenhouse: status node + rata-rata batch terbaru.
     *
     * Rata-rata dihitung dari batch terbaru setiap node yang masih online
     * (mengirim data dalam 15 menit terakhir). Node offline tidak dipakai
     * agar nilai basi tidak mendistorsi kondisi terkini.
     *
     * @return array<string, mixed>
     */
    public function greenhouseOverview(string $greenhouseId): array
    {
        $config = $this->greenhouseConfig()[$greenhouseId] ?? null;
        abort_if($config === null, 404);

        $batches = $this->latestBatchByNode($greenhouseId);
        $cutoff = now()->subMinutes($this->onlineThresholdMinutes());

        $onlineValues = [];
        $lastSeen = null;

        foreach ($config['nodes'] as $nodeId) {
            $batch = $batches[$nodeId] ?? null;

            if ($batch === null) {
                continue;
            }

            if ($lastSeen === null || $batch['timestamp']->greaterThan($lastSeen)) {
                $lastSeen = $batch['timestamp'];
            }

            if ($batch['timestamp']->greaterThanOrEqualTo($cutoff)) {
                $onlineValues[$nodeId] = $batch['values'];
            }
        }

        $averages = [];

        foreach (array_keys($this->parameterConfig()) as $parameter) {
            $values = [];

            foreach ($onlineValues as $nodeValues) {
                if (isset($nodeValues[$parameter]) && is_numeric($nodeValues[$parameter])) {
                    $values[] = (float) $nodeValues[$parameter];
                }
            }

            $averages[$parameter] = $values === []
                ? null
                : round(array_sum($values) / count($values), 2);
        }

        return [
            'id' => $greenhouseId,
            'name' => $config['name'],
            'location' => $config['location'] ?? null,
            'nodes_total' => count($config['nodes']),
            'nodes_online' => count($onlineValues),
            'online_nodes' => array_keys($onlineValues),
            'last_seen' => $lastSeen,
            'last_seen_label' => $this->lastSeenLabel($lastSeen),
            'averages' => $averages,
        ];
    }

    /**
     * Detail seluruh node di semua greenhouse.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function nodesOverview(): array
    {
        $overview = [];

        foreach (array_keys($this->greenhouseConfig()) as $greenhouseId) {
            $overview[$greenhouseId] = $this->nodes($greenhouseId);
        }

        return $overview;
    }

    /**
     * Detail node pada satu greenhouse.
     *
     * @return list<array<string, mixed>>
     */
    public function nodes(string $greenhouseId): array
    {
        $config = $this->greenhouseConfig()[$greenhouseId] ?? null;
        abort_if($config === null, 404);

        $batches = $this->latestBatchByNode($greenhouseId);
        $cutoff = now()->subMinutes($this->onlineThresholdMinutes());

        $nodes = [];

        foreach ($config['nodes'] as $index => $nodeId) {
            $batch = $batches[$nodeId] ?? null;
            $values = [];

            foreach (array_keys($this->parameterConfig()) as $parameter) {
                $values[$parameter] = $batch === null
                    ? null
                    : ($batch['values'][$parameter] ?? null);
            }

            $lastSeen = $batch['timestamp'] ?? null;

            $nodes[] = [
                'id' => $nodeId,
                'label' => 'Node '.($index + 1),
                'online' => $batch !== null && $lastSeen->greaterThanOrEqualTo($cutoff),
                'last_seen' => $lastSeen,
                'last_seen_label' => $this->lastSeenLabel($lastSeen),
                'values' => $values,
            ];
        }

        return $nodes;
    }

    /**
     * Data grafik riwayat untuk filter terpilih.
     *
     * @return array<string, mixed>
     */
    public function history(string $greenhouseId, string $source, string $parameter, string $range): array
    {
        $rows = $this->historyQuery($greenhouseId, $source, $parameter, $range)
            ->orderBy('created_at')
            ->get(['value', 'created_at']);

        $buckets = $this->emptyBuckets($range);

        foreach ($rows as $row) {
            $key = $this->bucketKey($row->created_at, $range);

            if (! isset($buckets[$key])) {
                continue;
            }

            $buckets[$key]['sum'] += (float) $row->value;
            $buckets[$key]['count']++;
        }

        $labels = [];
        $values = [];

        foreach ($buckets as $bucket) {
            $labels[] = $bucket['label'];
            $values[] = $bucket['count'] === 0
                ? null
                : round($bucket['sum'] / $bucket['count'], 2);
        }

        $validValues = array_values(array_filter($values, fn ($value) => $value !== null));

        $stats = [
            'min' => $validValues === [] ? null : min($validValues),
            'max' => $validValues === [] ? null : max($validValues),
            'avg' => $validValues === []
                ? null
                : round(array_sum($validValues) / count($validValues), 2),
            'latest' => $validValues === []
                ? null
                : $validValues[count($validValues) - 1],
        ];

        $parameterMeta = $this->parameterConfig()[$parameter];
        $greenhouseConfig = $this->greenhouseConfig()[$greenhouseId];

        return [
            'greenhouse' => $greenhouseId,
            'greenhouse_name' => $greenhouseConfig['name'],
            'source' => $source,
            'source_label' => $source === 'avg'
                ? 'Rata-rata '.$greenhouseConfig['name']
                : 'Node '.substr($source, 1),
            'parameter' => $parameter,
            'parameter_label' => $parameterMeta['label'],
            'unit' => $parameterMeta['unit'],
            'range' => $range,
            'labels' => $labels,
            'values' => $values,
            'stats' => $stats,
            'points' => count($labels),
        ];
    }

    /**
     * Query mentah untuk tabel riwayat (dipaginasi oleh controller).
     */
    public function historyQuery(string $greenhouseId, string $source, string $parameter, string $range): Builder
    {
        return SensorLog::query()
            ->forGreenhouse($greenhouseId)
            ->forParameter($parameter)
            ->when($source !== 'avg', fn (Builder $query) => $query->forNode($source))
            ->where('created_at', '>=', $this->rangeStart($range));
    }

    /**
     * Label waktu relatif berbahasa Indonesia tanpa bergantung locale Carbon.
     */
    public function lastSeenLabel(?CarbonInterface $timestamp): string
    {
        if ($timestamp === null) {
            return 'Belum ada data';
        }

        $seconds = max(0, now()->getTimestamp() - $timestamp->getTimestamp());

        if ($seconds < 60) {
            return 'baru saja';
        }

        if ($seconds < 3600) {
            return floor($seconds / 60).' menit lalu';
        }

        if ($seconds < 86400) {
            return floor($seconds / 3600).' jam lalu';
        }

        return floor($seconds / 86400).' hari lalu';
    }

    /**
     * Ambil baris pada timestamp terakhir masing-masing node.
     *
     * @return array<string, array{timestamp: CarbonInterface, values: array<string, float>}>
     */
    private function latestBatchByNode(string $greenhouseId): array
    {
        $latestTimes = SensorLog::query()
            ->forGreenhouse($greenhouseId)
            ->selectRaw('node_id, MAX(created_at) as latest_at')
            ->groupBy('node_id')
            ->pluck('latest_at', 'node_id');

        if ($latestTimes->isEmpty()) {
            return [];
        }

        $rows = SensorLog::query()
            ->forGreenhouse($greenhouseId)
            ->where(function (Builder $query) use ($latestTimes) {
                foreach ($latestTimes as $nodeId => $latestAt) {
                    $query->orWhere(function (Builder $inner) use ($nodeId, $latestAt) {
                        $inner->where('node_id', $nodeId)->where('created_at', $latestAt);
                    });
                }
            })
            ->get(['node_id', 'parameter', 'value', 'created_at']);

        $batches = [];

        foreach ($rows as $row) {
            $batches[$row->node_id]['timestamp'] = $row->created_at;
            $batches[$row->node_id]['values'][$row->parameter] = $row->value;
        }

        return $batches;
    }

    private function rangeStart(string $range): CarbonInterface
    {
        return match ($range) {
            '7d' => $this->alignToStep(now()->subDays(7)->startOfHour(), 6),
            '30d' => now()->subDays(29)->startOfDay(),
            default => now()->subHours(23)->startOfHour(),
        };
    }

    private function alignToStep(CarbonInterface $at, int $stepHours): CarbonInterface
    {
        $aligned = $at->copy();
        $aligned->setTime(intdiv($aligned->hour, $stepHours) * $stepHours, 0);

        return $aligned;
    }

    /**
     * @return array<string, array{label: string, sum: float, count: int}>
     */
    private function emptyBuckets(string $range): array
    {
        $cursor = $this->rangeStart($range);
        $now = now();
        $buckets = [];

        while ($cursor->lessThanOrEqualTo($now)) {
            $key = $this->bucketKey($cursor, $range);
            $buckets[$key] = [
                'label' => $this->bucketLabel($cursor, $range),
                'sum' => 0.0,
                'count' => 0,
            ];

            $cursor = match ($range) {
                '7d' => $cursor->copy()->addHours(6),
                '30d' => $cursor->copy()->addDay(),
                default => $cursor->copy()->addHour(),
            };
        }

        return $buckets;
    }

    private function bucketKey(CarbonInterface $at, string $range): string
    {
        return match ($range) {
            '7d' => $at->format('Y-m-d').' '.str_pad(
                (string) (intdiv($at->hour, 6) * 6),
                2,
                '0',
                STR_PAD_LEFT
            ),
            '30d' => $at->format('Y-m-d'),
            default => $at->format('Y-m-d H'),
        };
    }

    private function bucketLabel(CarbonInterface $at, string $range): string
    {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $month = $months[(int) $at->format('n') - 1];

        return match ($range) {
            '7d' => $at->format('d').' '.$month.' '.$at->format('H').':00',
            '30d' => $at->format('d').' '.$month,
            default => $at->format('H').':00',
        };
    }
}

