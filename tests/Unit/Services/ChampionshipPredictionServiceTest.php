<?php

namespace Tests\Unit\Services;

use App\Models\FootballMatch;
use App\Models\Season;
use App\Models\Team;
use App\Services\ChampionshipPredictionService;
use App\Services\LeagueTableService;
use App\Services\MatchSimulationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChampionshipPredictionServiceTest extends TestCase
{
    #[Test]
    public function it_returns_null_before_prediction_window(): void
    {
        $season = new Season(['current_week' => 3, 'total_weeks' => 6]);
        $teams = collect([
            new Team(['id' => 1, 'name' => 'A', 'short_name' => 'A']),
        ]);

        $service = new ChampionshipPredictionService(
            new MatchSimulationService(123),
            new LeagueTableService,
        );

        $this->assertNull($service->predict($season, $teams, collect()));
    }

    #[Test]
    public function it_returns_one_hundred_percent_when_title_is_clinched(): void
    {
        $season = new Season(['current_week' => 5, 'total_weeks' => 6]);
        $teams = collect([
            new Team(['id' => 1, 'name' => 'Leader', 'short_name' => 'Leader', 'power_rating' => 90, 'home_advantage' => 5, 'supporter_strength' => 5, 'goalkeeper_factor' => 5]),
            new Team(['id' => 2, 'name' => 'Chaser', 'short_name' => 'Chaser', 'power_rating' => 70, 'home_advantage' => 5, 'supporter_strength' => 5, 'goalkeeper_factor' => 5]),
            new Team(['id' => 3, 'name' => 'Third', 'short_name' => 'Third', 'power_rating' => 60, 'home_advantage' => 5, 'supporter_strength' => 5, 'goalkeeper_factor' => 5]),
            new Team(['id' => 4, 'name' => 'Fourth', 'short_name' => 'Fourth', 'power_rating' => 50, 'home_advantage' => 5, 'supporter_strength' => 5, 'goalkeeper_factor' => 5]),
        ]);

        $played = collect([
            $this->match(1, 2, 3, 0, 1),
            $this->match(2, 1, 0, 3, 2),
            $this->match(1, 3, 3, 0, 3),
        ]);

        $remaining = collect([
            $this->match(3, 4, null, null, null, FootballMatch::STATUS_SCHEDULED, 6),
        ]);

        $service = new ChampionshipPredictionService(
            new MatchSimulationService(123),
            new LeagueTableService,
        );

        $predictions = $service->predict($season, $teams, $played->merge($remaining));

        $this->assertNotNull($predictions);
        $leader = collect($predictions)->firstWhere('team_id', 1);
        $chaser = collect($predictions)->firstWhere('team_id', 2);
        $this->assertSame(100.0, $leader['percentage']);
        $this->assertSame(0.0, $chaser['percentage']);
    }

    #[Test]
    public function monte_carlo_predictions_sum_to_one_hundred(): void
    {
        $season = new Season(['current_week' => 4, 'total_weeks' => 6]);
        $teams = collect([
            new Team(['id' => 1, 'name' => 'Alpha', 'short_name' => 'Alpha', 'power_rating' => 85, 'home_advantage' => 6, 'supporter_strength' => 6, 'goalkeeper_factor' => 6]),
            new Team(['id' => 2, 'name' => 'Bravo', 'short_name' => 'Bravo', 'power_rating' => 80, 'home_advantage' => 6, 'supporter_strength' => 6, 'goalkeeper_factor' => 6]),
            new Team(['id' => 3, 'name' => 'Charlie', 'short_name' => 'Charlie', 'power_rating' => 70, 'home_advantage' => 6, 'supporter_strength' => 6, 'goalkeeper_factor' => 6]),
            new Team(['id' => 4, 'name' => 'Delta', 'short_name' => 'Delta', 'power_rating' => 60, 'home_advantage' => 6, 'supporter_strength' => 6, 'goalkeeper_factor' => 6]),
        ]);

        $played = collect([
            $this->match(1, 2, 1, 0, 1),
            $this->match(3, 4, 2, 2, 3),
        ]);

        $remaining = collect([
            $this->match(1, 3, null, null, null, FootballMatch::STATUS_SCHEDULED, 4),
            $this->match(2, 4, null, null, null, FootballMatch::STATUS_SCHEDULED, 4),
            $this->match(1, 4, null, null, null, FootballMatch::STATUS_SCHEDULED, 5),
            $this->match(2, 3, null, null, null, FootballMatch::STATUS_SCHEDULED, 5),
        ]);

        $service = new ChampionshipPredictionService(
            new MatchSimulationService(999),
            new LeagueTableService,
        );

        $predictions = $service->predict($season, $teams, $played->merge($remaining));

        $this->assertNotNull($predictions);
        $total = collect($predictions)->sum('percentage');
        $this->assertEqualsWithDelta(100.0, $total, 1.0);
    }

    private function match(
        int $homeId,
        int $awayId,
        ?int $homeGoals,
        ?int $awayGoals,
        ?int $week = 1,
        string $status = FootballMatch::STATUS_PLAYED,
    ): FootballMatch {
        return new FootballMatch([
            'home_team_id' => $homeId,
            'away_team_id' => $awayId,
            'home_goals' => $homeGoals,
            'away_goals' => $awayGoals,
            'week' => $week,
            'status' => $status,
        ]);
    }
}
