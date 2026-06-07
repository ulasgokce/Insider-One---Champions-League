<?php

namespace App\Services;

use App\Models\Team;
use Random\Engine\Mt19937;
use Random\Randomizer;

class MatchSimulationService
{
    private Randomizer $randomizer;

    public function __construct(?int $seed = null)
    {
        $engine = new Mt19937($seed ?? random_int(1, PHP_INT_MAX));
        $this->randomizer = new Randomizer($engine);
    }

    public function withSeed(int $seed): self
    {
        return new self($seed);
    }

    /**
     * @return array{home_goals: int, away_goals: int}
     */
    public function simulate(Team $homeTeam, Team $awayTeam): array
    {
        $homeStrength = max($homeTeam->effectiveStrength(true), 1.0);
        $awayStrength = max($awayTeam->effectiveStrength(false), 1.0);

        $ratio = $homeStrength / $awayStrength;

        $homeLambda = max(0.35, min(3.2, 1.35 * sqrt($ratio)));
        $awayLambda = max(0.25, min(2.8, 1.15 / sqrt($ratio)));

        return [
            'home_goals' => $this->samplePoisson($homeLambda),
            'away_goals' => $this->samplePoisson($awayLambda),
        ];
    }

    private function samplePoisson(float $lambda): int
    {
        $limit = exp(-$lambda);
        $product = 1.0;
        $count = 0;

        do {
            $count++;
            $product *= $this->randomizer->getFloat(0, 1);
        } while ($product > $limit && $count < 15);

        return max(0, $count - 1);
    }
}
