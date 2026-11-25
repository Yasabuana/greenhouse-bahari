<?php

namespace App\Http\Controllers;

use App\Services\SensorDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class NodeController extends Controller
{
    public function __construct(private readonly SensorDataService $sensorData)
    {
    }

    public function index(): View
    {
        return view('nodes', [
            'nodes' => $this->sensorData->nodesOverview(),
            'greenhouseConfig' => $this->sensorData->greenhouseConfig(),
            'parameters' => $this->sensorData->parameterConfig(),
            'onlineThreshold' => $this->sensorData->onlineThresholdMinutes(),
        ]);
    }

    /**
     * Endpoint status node untuk memperbarui badge/last seen.
     */
    public function data(): JsonResponse
    {
        return response()
            ->json([
                'updated_at' => now()->toIso8601String(),
                'updated_at_label' => now()->format('H:i:s'),
                'nodes' => $this->sensorData->nodesOverview(),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
