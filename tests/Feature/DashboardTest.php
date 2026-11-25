<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesSensorLogs;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use CreatesSensorLogs;
    use RefreshDatabase;

    public function test_dashboard_page_renders_both_greenhouses(): void
    {
        $this->logBatch('GH1', 'N1', now());

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Greenhouse 1')
            ->assertSee('Greenhouse 2')
            ->assertSee('Kondisi Udara')
            ->assertSee('Kondisi Media Tanam')
            ->assertSee('1/3 Node Online')
            ->assertSee('0/2 Node Online')
            ->assertSee('Dashboard');
    }

    public function test_dashboard_averages_only_include_online_nodes(): void
    {
        $now = now();

        $this->logBatch('GH1', 'N1', $now, ['air_temp' => 10]);
        $this->logBatch('GH1', 'N2', $now->copy()->subMinutes(14), ['air_temp' => 20]);
        $this->logBatch('GH1', 'N3', $now->copy()->subMinutes(30), ['air_temp' => 30]);

        $response = $this->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonPath('greenhouses.GH1.nodes_total', 3)
            ->assertJsonPath('greenhouses.GH1.nodes_online', 2)
            ->assertJsonPath('greenhouses.GH1.averages.air_temp', 15)
            ->assertJsonPath('greenhouses.GH2.nodes_total', 2)
            ->assertJsonPath('greenhouses.GH2.nodes_online', 0)
            ->assertJsonPath('greenhouses.GH2.averages.air_temp', null);
    }

    public function test_dashboard_uses_latest_batch_per_node(): void
    {
        $now = now();

        $this->logBatch('GH2', 'N1', $now->copy()->subMinutes(20), ['air_temp' => 5]);
        $this->logBatch('GH2', 'N1', $now, ['air_temp' => 35]);

        $response = $this->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonPath('greenhouses.GH2.nodes_online', 1)
            ->assertJsonPath('greenhouses.GH2.averages.air_temp', 35);
    }

    public function test_dashboard_returns_empty_averages_when_there_is_no_data(): void
    {
        $response = $this->getJson('/api/dashboard');

        $response->assertOk()
            ->assertJsonPath('greenhouses.GH1.nodes_online', 0)
            ->assertJsonPath('greenhouses.GH1.last_seen', null)
            ->assertJsonPath('greenhouses.GH1.last_seen_label', 'Belum ada data');

        $this->assertCount(10, $response->json('greenhouses.GH1.averages'));
    }
}
