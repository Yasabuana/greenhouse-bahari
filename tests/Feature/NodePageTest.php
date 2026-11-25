<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesSensorLogs;
use Tests\TestCase;

class NodePageTest extends TestCase
{
    use CreatesSensorLogs;
    use RefreshDatabase;

    public function test_nodes_page_lists_five_nodes_across_two_greenhouses(): void
    {
        $this->logBatch('GH1', 'N1', now());
        $this->logBatch('GH1', 'N2', now());
        $this->logBatch('GH1', 'N3', now());
        $this->logBatch('GH2', 'N1', now());
        $this->logBatch('GH2', 'N2', now());

        $response = $this->get('/nodes');

        $response->assertOk()
            ->assertSee('Greenhouse 1')
            ->assertSee('Greenhouse 2')
            ->assertSee('Node 1')
            ->assertSee('Node 2')
            ->assertSee('Node 3')
            ->assertSee('Terakhir masuk:')
            ->assertSee('Media Tanam');
    }

    public function test_nodes_endpoint_flags_stale_node_as_offline(): void
    {
        $now = now();

        $this->logBatch('GH1', 'N1', $now);
        $this->logBatch('GH1', 'N2', $now->copy()->subMinutes(5));
        $this->logBatch('GH1', 'N3', $now->copy()->subMinutes(60));
        $this->logBatch('GH2', 'N1', $now);
        $this->logBatch('GH2', 'N2', $now->copy()->subMinutes(45));

        $response = $this->getJson('/api/nodes');

        $response->assertOk()
            ->assertJsonPath('nodes.GH1.0.id', 'N1')
            ->assertJsonPath('nodes.GH1.0.online', true)
            ->assertJsonPath('nodes.GH1.1.online', true)
            ->assertJsonPath('nodes.GH1.2.id', 'N3')
            ->assertJsonPath('nodes.GH1.2.online', false)
            ->assertJsonPath('nodes.GH2.0.online', true)
            ->assertJsonPath('nodes.GH2.1.online', false)
            ->assertJsonCount(3, 'nodes.GH1')
            ->assertJsonCount(2, 'nodes.GH2');
    }

    public function test_nodes_endpoint_returns_null_values_for_nodes_without_data(): void
    {
        $response = $this->getJson('/api/nodes');

        $response->assertOk()
            ->assertJsonPath('nodes.GH1.0.online', false)
            ->assertJsonPath('nodes.GH1.0.last_seen', null)
            ->assertJsonPath('nodes.GH1.0.last_seen_label', 'Belum ada data')
            ->assertJsonPath('nodes.GH1.0.values.air_temp', null);
    }
}
