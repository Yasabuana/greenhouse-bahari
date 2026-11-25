<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSensorReadingRequest;
use App\Models\SensorLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SensorController extends Controller
{
    /**
     * Terima satu paket bacaan 10 parameter dan simpan sebagai 10 baris log.
     */
    public function __invoke(StoreSensorReadingRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $recordedAt = now();

        $rows = [];

        foreach (array_keys(config('greenhouse.parameters')) as $parameter) {
            $rows[] = [
                'greenhouse_id' => $validated['gh'],
                'node_id' => $validated['node'],
                'parameter' => $parameter,
                'value' => (float) $validated[$parameter],
                'created_at' => $recordedAt,
                'updated_at' => $recordedAt,
            ];
        }

        DB::transaction(fn () => SensorLog::query()->insert($rows));

        return response()->json([
            'status' => 'ok',
            'message' => 'Data sensor diterima.',
            'data' => [
                'greenhouse_id' => $validated['gh'],
                'node_id' => $validated['node'],
                'recorded_at' => $recordedAt->toIso8601String(),
                'stored' => count($rows),
                'parameters' => collect($rows)
                    ->mapWithKeys(fn (array $row) => [$row['parameter'] => $row['value']])
                    ->all(),
            ],
        ], 201);
    }
}
