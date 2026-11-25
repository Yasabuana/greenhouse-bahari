<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_defaults_to_light_theme_and_exposes_theme_toggle(): void
    {
        foreach (['/', '/nodes', '/history'] as $uri) {
            $response = $this->get($uri);

            $response->assertOk()
                ->assertDontSee('<html lang="id" class="dark"', false)
                ->assertSee("var theme = 'light';", false)
                ->assertSee("localStorage.getItem('theme') === 'dark' ? 'dark' : 'light'", false)
                ->assertSee("classList.toggle('dark'", false)
                ->assertSee('themeToggle()', false)
                ->assertSee('dark:bg-night-950/80', false);
        }
    }
}
