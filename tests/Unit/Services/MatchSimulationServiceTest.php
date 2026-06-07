<?php

namespace Tests\Unit\Services;

use App\Models\Team;
use App\Services\MatchSimulationService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MatchSimulationServiceTest extends TestCase
{
    #[Test]
    public function stronger_team_wins_more_often_than_weaker_team(): void
    {
        $strong = new Team([
            'power_rating' => 95,
            'home_advantage' => 8,
            'supporter_strength' => 9,
            'goalkeeper_factor' => 9,
        ]);

        $weak = new Team([
            'power_rating' => 55,
            'home_advantage' => 5,
            'supporter_strength' => 5,
            'goalkeeper_factor' => 5,
        ]);

        $strongWins = 0;
        $weakWins = 0;
        $iterations = 500;

        for ($seed = 1; $seed <= $iterations; $seed++) {
            $service = new MatchSimulationService($seed);
            $result = $service->simulate($strong, $weak);

            if ($result['home_goals'] > $result['away_goals']) {
                $strongWins++;
            } elseif ($result['away_goals'] > $result['home_goals']) {
                $weakWins++;
            }
        }

        $this->assertGreaterThan($iterations * 0.45, $strongWins);
        $this->assertLessThan($iterations * 0.25, $weakWins);
    }

    #[Test]
    public function extreme_strength_gap_produces_rare_upsets(): void
    {
        $elite = new Team([
            'power_rating' => 100,
            'home_advantage' => 10,
            'supporter_strength' => 10,
            'goalkeeper_factor' => 10,
        ]);

        $minnow = new Team([
            'power_rating' => 10,
            'home_advantage' => 2,
            'supporter_strength' => 2,
            'goalkeeper_factor' => 2,
        ]);

        $minnowWins = 0;
        $iterations = 1000;

        for ($seed = 1; $seed <= $iterations; $seed++) {
            $service = new MatchSimulationService($seed);
            $result = $service->simulate($minnow, $elite);

            if ($result['home_goals'] > $result['away_goals']) {
                $minnowWins++;
            }
        }

        $this->assertLessThan($iterations * 0.05, $minnowWins);
    }
}
