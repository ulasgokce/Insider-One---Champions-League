<?php

namespace Tests\Unit\Services;

use App\Models\FootballMatch;
use App\Models\Team;
use App\Services\LeagueTableService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeagueTableServiceTest extends TestCase
{
    #[Test]
    public function it_calculates_points_and_orders_by_premier_league_rules(): void
    {
        $teams = collect([
            new Team(['id' => 1, 'name' => 'Alpha', 'short_name' => 'Alpha', 'country' => 'AAA']),
            new Team(['id' => 2, 'name' => 'Bravo', 'short_name' => 'Bravo', 'country' => 'BBB']),
            new Team(['id' => 3, 'name' => 'Charlie', 'short_name' => 'Charlie', 'country' => 'CCC']),
        ]);

        $matches = collect([
            $this->makeMatch(1, 2, 2, 0, FootballMatch::STATUS_PLAYED),
            $this->makeMatch(2, 3, 1, 1, FootballMatch::STATUS_PLAYED),
            $this->makeMatch(3, 1, 0, 3, FootballMatch::STATUS_PLAYED),
        ]);

        $service = new LeagueTableService;
        $table = $service->calculate($teams, $matches);

        $this->assertSame('Alpha', $table[0]['name']);
        $this->assertSame(6, $table[0]['points']);
        $this->assertSame('Bravo', $table[1]['name']);
        $this->assertSame(1, $table[1]['points']);
        $this->assertSame('Charlie', $table[2]['name']);
        $this->assertSame(1, $table[2]['points']);
    }

    #[Test]
    public function it_breaks_ties_by_goal_difference_then_goals_scored(): void
    {
        $teams = collect([
            new Team(['id' => 1, 'name' => 'Alpha', 'short_name' => 'Alpha', 'country' => 'AAA']),
            new Team(['id' => 2, 'name' => 'Bravo', 'short_name' => 'Bravo', 'country' => 'BBB']),
        ]);

        $matches = collect([
            $this->makeMatch(1, 2, 3, 1, FootballMatch::STATUS_PLAYED),
            $this->makeMatch(2, 1, 2, 2, FootballMatch::STATUS_PLAYED),
        ]);

        $service = new LeagueTableService;
        $table = $service->calculate($teams, $matches);

        $this->assertSame('Alpha', $table[0]['name']);
        $this->assertSame(4, $table[0]['points']);
        $this->assertSame(2, $table[0]['goal_difference']);
    }

    private function makeMatch(int $homeId, int $awayId, int $homeGoals, int $awayGoals, string $status): FootballMatch
    {
        $match = new FootballMatch([
            'home_team_id' => $homeId,
            'away_team_id' => $awayId,
            'home_goals' => $homeGoals,
            'away_goals' => $awayGoals,
            'status' => $status,
        ]);

        return $match;
    }
}
