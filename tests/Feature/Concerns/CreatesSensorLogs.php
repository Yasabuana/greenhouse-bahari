<?php

namespace Tests\Feature\Concerns;

use App\Models\SensorLog;
use Carbon\CarbonInterface;

trait CreatesSensorLogs
{
    /**
     * Simpan satu batch lengkap (10 parameter) untuk satu node.
     *
     * @param  array<string, float|int>  $values
     */
    protected function logBatch(string $greenhouseId, string $nodeId, CarbonInterface $at, array $values = []): void
    {
        $rows = [];

        foreach (array_keys(config('greenhouse.parameters')) as $parameter) {
            $rows[] = [
                'greenhouse_id' => $greenhouseId,
                'node_id' => $nodeId,
                'parameter' => $parameter,
                'value' => (float) ($values[$parameter] ?? 10),
                'created_at' => $at,
                'updated_at' => $at,
            ];
        }

        SensorLog::query()->insert($rows);
    }
}
