<?php

namespace Tests\Feature;

use App\Models\SensorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SensorIngestionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'gh' => 'GH1',
            'node' => 'N1',
            'n' => 45,
            'p' => 22,
            'k' => 38,
            'soil_moisture' => 64.5,
            'soil_temp' => 27.2,
            'ph' => 6.5,
            'ec' => 1280,
            'salinity' => 620,
            'air_temp' => 30.4,
            'air_humidity' => 71.2,
        ], $overrides);
    }

    public function test_it_stores_a_complete_batch_of_ten_parameters(): void
    {
        $response = $this->postJson('/api/sensor', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('data.greenhouse_id', 'GH1')
            ->assertJsonPath('data.node_id', 'N1')
            ->assertJsonPath('data.stored', 10);

        $this->assertDatabaseCount('sensor_logs', 10);
        $this->assertDatabaseHas('sensor_logs', [
            'greenhouse_id' => 'GH1',
            'node_id' => 'N1',
            'parameter' => 'ph',
            'value' => 6.5,
        ]);

        foreach (array_keys(config('greenhouse.parameters')) as $parameter) {
            $this->assertDatabaseHas('sensor_logs', [
                'greenhouse_id' => 'GH1',
                'node_id' => 'N1',
                'parameter' => $parameter,
            ]);
        }

        $uniqueTimestamps = SensorLog::query()->pluck('created_at')->unique();

        $this->assertCount(1, $uniqueTimestamps, 'Semua parameter dalam satu batch harus memakai timestamp yang sama.');
    }

    public function test_it_rejects_a_node_that_does_not_belong_to_the_greenhouse(): void
    {
        $response = $this->postJson('/api/sensor', $this->payload([
            'gh' => 'GH2',
            'node' => 'N3',
        ]));

        $response->assertStatus(422)->assertJsonValidationErrors('node');
        $this->assertDatabaseCount('sensor_logs', 0);
    }

    public function test_it_rejects_out_of_range_sensor_values(): void
    {
        $response = $this->postJson('/api/sensor', $this->payload([
            'air_humidity' => 150,
            'ph' => 20,
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['air_humidity', 'ph']);

        $this->assertDatabaseCount('sensor_logs', 0);
    }

    public function test_it_requires_all_ten_parameters(): void
    {
        $payload = $this->payload();
        unset($payload['ec']);

        $response = $this->postJson('/api/sensor', $payload);

        $response->assertStatus(422)->assertJsonValidationErrors('ec');
        $this->assertDatabaseCount('sensor_logs', 0);
    }

    public function test_it_accepts_each_node_of_both_greenhouses(): void
    {
        foreach (config('greenhouse.greenhouses') as $greenhouseId => $greenhouse) {
            foreach ($greenhouse['nodes'] as $nodeId) {
                $this->postJson('/api/sensor', $this->payload([
                    'gh' => $greenhouseId,
                    'node' => $nodeId,
                ]))->assertCreated();
            }
        }

        $this->assertDatabaseCount('sensor_logs', 50);
    }
}
