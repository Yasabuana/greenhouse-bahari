<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesSensorLogs;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use CreatesSensorLogs;
    use RefreshDatabase;

    public function test_history_page_renders_chart_and_table(): void
    {
        $this->logBatch('GH1', 'N1', now()->subHours(2), ['air_temp' => 30]);
        $this->logBatch('GH1', 'N2', now()->subHour(), ['air_temp' => 20]);
        $this->logBatch('GH2', 'N1', now()->subMinutes(10), ['air_temp' => 99]);

        $response = $this->get('/history?gh=GH1&source=avg&parameter=air_temp&range=24h');

        $response->assertOk()
            ->assertSee('Riwayat Data')
            ->assertSee('Suhu Udara')
            ->assertSee('Rata-rata Greenhouse 1');

        $chart = $response->viewData('chart');
        $filters = $response->viewData('filters');

        $this->assertSame('GH1', $filters['gh']);
        $this->assertSame('avg', $filters['source']);
        $this->assertSame('air_temp', $filters['parameter']);
        $this->assertSame('24h', $filters['range']);

        $this->assertSame(24, $chart['points']);
        $this->assertSame(20.0, (float) $chart['stats']['min']);
        $this->assertSame(30.0, (float) $chart['stats']['max']);
        $this->assertSame(25.0, (float) $chart['stats']['avg']);
        $this->assertSame(20.0, (float) $chart['stats']['latest']);

        $response->assertViewHas('logs', fn ($logs) => $logs->total() === 2);
    }

    public function test_history_can_filter_a_single_node(): void
    {
        $this->logBatch('GH1', 'N1', now()->subHours(2), ['air_temp' => 30]);
        $this->logBatch('GH1', 'N2', now()->subHour(), ['air_temp' => 20]);

        $response = $this->get('/history?gh=GH1&source=N1&parameter=air_temp&range=24h');

        $response->assertOk();

        $chart = $response->viewData('chart');

        $this->assertSame('N1', $chart['source']);
        $this->assertSame('Node 1', $chart['source_label']);
        $this->assertSame(30.0, (float) $chart['stats']['max']);
        $response->assertViewHas('logs', fn ($logs) => $logs->total() === 1);
    }

    public function test_history_supports_seven_day_and_thirty_day_ranges(): void
    {
        $this->logBatch('GH1', 'N1', now()->subDays(2), ['air_temp' => 26]);

        $weekly = $this->get('/history?gh=GH1&source=avg&parameter=air_temp&range=7d');
        $weekly->assertOk();

        $weeklyPoints = $weekly->viewData('chart')['points'];
        $this->assertGreaterThanOrEqual(28, $weeklyPoints);
        $this->assertLessThanOrEqual(30, $weeklyPoints);
        $this->assertNotNull($weekly->viewData('chart')['stats']['max']);

        $monthly = $this->get('/history?gh=GH1&source=avg&parameter=air_temp&range=30d');
        $monthly->assertOk();
        $this->assertSame(30, $monthly->viewData('chart')['points']);
        $this->assertNotNull($monthly->viewData('chart')['stats']['max']);
    }

    public function test_history_falls_back_to_safe_defaults_for_invalid_filters(): void
    {
        $response = $this->get('/history?gh=GHX&source=N9&parameter=bogus&range=99y');

        $response->assertOk();

        $filters = $response->viewData('filters');

        $this->assertSame('GH1', $filters['gh']);
        $this->assertSame('avg', $filters['source']);
        $this->assertSame('air_temp', $filters['parameter']);
        $this->assertSame('24h', $filters['range']);
    }

    public function test_history_filters_render_the_selected_options(): void
    {
        $this->logBatch('GH1', 'N2', now(), ['air_temp' => 27]);

        $response = $this->get('/history?gh=GH1&source=N2&parameter=soil_moisture&range=7d');

        $response->assertOk()
            ->assertSee('value="GH1" selected', false)
            ->assertSee('value="N2" selected', false)
            ->assertSee('value="soil_moisture" selected', false)
            ->assertSee('value="7d" selected', false);

        // Node options mengikuti greenhouse terpilih: GH1 punya 3 node, GH2 punya 2 node.
        $this->assertSame(3, substr_count($response->getContent(), '<option value="N'));

        $gh2 = $this->get('/history?gh=GH2&source=avg');

        $gh2->assertOk();
        $this->assertSame(2, substr_count($gh2->getContent(), '<option value="N'));
    }

    public function test_history_table_is_paginated(): void
    {
        for ($minute = 1; $minute <= 20; $minute++) {
            $this->logBatch('GH1', 'N1', now()->subMinutes($minute * 2), ['air_temp' => 20 + $minute]);
        }

        $response = $this->get('/history?gh=GH1&source=N1&parameter=air_temp&range=24h');

        $response->assertOk()
            ->assertSee('Menampilkan');

        $response->assertViewHas('logs', fn ($logs) => $logs->total() === 20
            && $logs->count() === 15
            && $logs->hasMorePages());
    }
}
