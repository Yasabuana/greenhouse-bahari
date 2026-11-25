<?php

namespace App\Http\Controllers;

use App\Services\SensorDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly SensorDataService $sensorData)
    {
    }

    public function index(): View
    {
        return view('dashboard', [
            'greenhouses' => $this->sensorData->overview(),
            'greenhouseConfig' => $this->sensorData->greenhouseConfig(),
            'parameters' => $this->sensorData->parameterConfig(),
            'onlineThreshold' => $this->sensorData->onlineThresholdMinutes(),
        ]);
    }

    /**
     * Endpoint ringan untuk auto-refresh dashboard tiap 10 detik.
     */
    public function data(): JsonResponse
    {
        return response()
            ->json([
                'updated_at' => now()->toIso8601String(),
                'updated_at_label' => now()->format('H:i:s'),
                'greenhouses' => $this->sensorData->overview(),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
