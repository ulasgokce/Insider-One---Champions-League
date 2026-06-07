<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_league_state_endpoint_returns_successfully(): void
    {
        $response = $this->getJson('/api/league/state');

        $response->assertOk()
            ->assertJsonStructure([
                'season',
                'standings',
                'current_week_fixtures',
                'predictions',
                'results_by_week',
            ]);
    }
}
