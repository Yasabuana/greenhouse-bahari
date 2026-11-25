<?php

namespace Database\Seeders;

use App\Models\SensorLog;
use Illuminate\Database\Seeder;

class SensorLogSeeder extends Seeder
{
    /**
     * Seed realistic dummy readings for the last 24 hours (hourly).
     *
     * 2 greenhouses x 5 nodes x 10 parameters x 24 hours = 1.200 rows.
     */
    public function run(): void
    {
        $parameters = config('greenhouse.parameters');
        $greenhouses = config('greenhouse.greenhouses');

        // Profil pembeda tiap node agar grafik antar-node tidak identik.
        $profiles = [
            'GH1' => [
                'N1' => ['n' => 4.0, 'p' => 1.5, 'k' => 2.5, 'moisture' => -2.0, 'soil_temp' => 0.8, 'ph' => 0.08, 'ec' => 60, 'salinity' => 30, 'air_temp' => 0.7, 'humidity' => -2.5],
                'N2' => ['n' => 0.0, 'p' => 0.0, 'k' => 0.0, 'moisture' => 0.0, 'soil_temp' => 0.0, 'ph' => 0.0, 'ec' => 0, 'salinity' => 0, 'air_temp' => 0.0, 'humidity' => 0.0],
                'N3' => ['n' => -3.5, 'p' => -1.2, 'k' => -2.0, 'moisture' => 2.8, 'soil_temp' => -0.6, 'ph' => -0.07, 'ec' => -70, 'salinity' => -35, 'air_temp' => -0.5, 'humidity' => 2.0],
            ],
            'GH2' => [
                'N1' => ['n' => 2.5, 'p' => 1.0, 'k' => 1.8, 'moisture' => -1.5, 'soil_temp' => 0.5, 'ph' => 0.05, 'ec' => 45, 'salinity' => 20, 'air_temp' => 0.6, 'humidity' => -1.8],
                'N2' => ['n' => -2.0, 'p' => -0.8, 'k' => -1.4, 'moisture' => 2.2, 'soil_temp' => -0.4, 'ph' => -0.04, 'ec' => -50, 'salinity' => -25, 'air_temp' => -0.4, 'humidity' => 1.5],
            ],
        ];

        $noise = fn (float $amplitude): float => (mt_rand(-1000, 1000) / 1000) * $amplitude;
        $clamp = fn (float $value, float $min, float $max): float => max($min, min($max, $value));

        $now = now();
        $rows = [];

        foreach ($greenhouses as $greenhouseId => $greenhouse) {
            foreach ($greenhouse['nodes'] as $nodeIndex => $nodeId) {
                $profile = $profiles[$greenhouseId][$nodeId] ?? [];

                for ($hoursAgo = 23; $hoursAgo >= 0; $hoursAgo--) {
                    $at = $now->copy()->subHours($hoursAgo);
                    $at->setSecond(0);

                    // Puncak suhu sekitar pukul 14.00, terendah dini hari.
                    $phase = sin((($at->hour - 8) / 24) * 2 * M_PI);

                    $values = [
                        'n' => 42 + ($profile['n'] ?? 0) + 5 * $phase + $noise(2.0),
                        'p' => 24 + ($profile['p'] ?? 0) + 3 * $phase + $noise(1.2),
                        'k' => 36 + ($profile['k'] ?? 0) + 4 * $phase + $noise(1.5),
                        'soil_moisture' => 66 + ($profile['moisture'] ?? 0) - 4 * $phase + $noise(1.8),
                        'soil_temp' => 26 + ($profile['soil_temp'] ?? 0) + 2.6 * $phase + $noise(0.3),
                        'ph' => 6.45 + ($profile['ph'] ?? 0) + 0.12 * $phase + $noise(0.03),
                        'ec' => 1250 + ($profile['ec'] ?? 0) + 110 * $phase + $noise(35),
                        'salinity' => 610 + ($profile['salinity'] ?? 0) + 55 * $phase + $noise(18),
                        'air_temp' => 28.5 + ($profile['air_temp'] ?? 0) + 4.2 * $phase + $noise(0.5),
                        'air_humidity' => 72 + ($profile['humidity'] ?? 0) - 8 * $phase + $noise(1.5),
                    ];

                    foreach ($parameters as $parameter => $meta) {
                        $value = match ($parameter) {
                            'n', 'p', 'k' => $clamp($values[$parameter], 5, 200),
                            'soil_moisture', 'air_humidity' => $clamp($values[$parameter], 20, 98),
                            'soil_temp' => $clamp($values[$parameter], 15, 40),
                            'ph' => $clamp($values[$parameter], 4.5, 8.5),
                            'ec' => $clamp($values[$parameter], 300, 3000),
                            'salinity' => $clamp($values[$parameter], 100, 1500),
                            'air_temp' => $clamp($values[$parameter], 15, 45),
                            default => $values[$parameter],
                        };

                        $rows[] = [
                            'greenhouse_id' => $greenhouseId,
                            'node_id' => $nodeId,
                            'parameter' => $parameter,
                            'value' => round($value, $meta['decimals']),
                            'created_at' => $at,
                            'updated_at' => $at,
                        ];
                    }
                }
            }
        }

        SensorLog::query()->delete();

        foreach (array_chunk($rows, 500) as $chunk) {
            SensorLog::query()->insert($chunk);
        }
    }
}
