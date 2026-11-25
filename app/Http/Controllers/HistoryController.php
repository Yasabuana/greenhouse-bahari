<?php

namespace App\Http\Controllers;

use App\Services\SensorDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function __construct(private readonly SensorDataService $sensorData)
    {
    }

    public function index(Request $request): View
    {
        $greenhouseConfig = $this->sensorData->greenhouseConfig();
        $parameters = $this->sensorData->parameterConfig();
        $ranges = [
            '24h' => '24 Jam',
            '7d' => '7 Hari',
            '30d' => '30 Hari',
        ];

        $greenhouseId = (string) $request->query('gh', 'GH1');
        if (! array_key_exists($greenhouseId, $greenhouseConfig)) {
            $greenhouseId = (string) array_key_first($greenhouseConfig);
        }

        $parameter = (string) $request->query('parameter', 'air_temp');
        if (! array_key_exists($parameter, $parameters)) {
            $parameter = array_key_exists('air_temp', $parameters)
                ? 'air_temp'
                : (string) array_key_first($parameters);
        }

        $range = (string) $request->query('range', '24h');
        if (! array_key_exists($range, $ranges)) {
            $range = '24h';
        }

        $source = (string) $request->query('source', 'avg');
        $nodes = $greenhouseConfig[$greenhouseId]['nodes'];
        if ($source !== 'avg' && ! in_array($source, $nodes, true)) {
            $source = 'avg';
        }

        $filters = [
            'gh' => $greenhouseId,
            'source' => $source,
            'parameter' => $parameter,
            'range' => $range,
        ];

        $chart = $this->sensorData->history($greenhouseId, $source, $parameter, $range);

        $logs = $this->sensorData->historyQuery($greenhouseId, $source, $parameter, $range)
            ->latest('created_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('history', [
            'filters' => $filters,
            'chart' => $chart,
            'logs' => $logs,
            'greenhouseConfig' => $greenhouseConfig,
            'parameters' => $parameters,
            'ranges' => $ranges,
        ]);
    }
}
