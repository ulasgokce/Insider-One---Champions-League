<?php

namespace Tests\Unit\Services;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Services\FixtureGeneratorService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FixtureGeneratorServiceTest extends TestCase
{
    #[Test]
    public function it_generates_a_double_round_robin_for_four_teams(): void
    {
        $teams = collect([
            new Team(['id' => 1, 'name' => 'A']),
            new Team(['id' => 2, 'name' => 'B']),
            new Team(['id' => 3, 'name' => 'C']),
            new Team(['id' => 4, 'name' => 'D']),
        ]);

        $service = new FixtureGeneratorService;
        $fixtures = $service->generate($teams);

        $this->assertCount(12, $fixtures);
        $this->assertSame(6, max(array_column($fixtures, 'week')));

        $weekCounts = collect($fixtures)->countBy('week');
        foreach ($weekCounts as $count) {
            $this->assertSame(2, $count);
        }

        $pairings = [];
        foreach ($fixtures as $fixture) {
            $key = $this->pairKey($fixture['home_team_id'], $fixture['away_team_id']);
            $pairings[$key] = ($pairings[$key] ?? 0) + 1;
        }

        $this->assertCount(12, $pairings);
        foreach ($pairings as $count) {
            $this->assertSame(1, $count);
        }

        foreach ($pairings as $key => $_) {
            $parts = explode('-', $key);
            $reverse = $parts[1].'-'.$parts[0];
            $this->assertArrayHasKey($reverse, $pairings);
        }
    }

    private function pairKey(int $home, int $away): string
    {
        return $home.'-'.$away;
    }
}
