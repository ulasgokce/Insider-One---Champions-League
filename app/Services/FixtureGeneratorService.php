<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Collection;

class FixtureGeneratorService
{
    /**
     * @param  Collection<int, Team>  $teams
     * @return array<int, array{week: int, home_team_id: int, away_team_id: int}>
     */
    public function generate(Collection $teams): array
    {
        $teamIds = $teams->pluck('id')->values()->all();
        $count = count($teamIds);

        if ($count < 2 || $count % 2 !== 0) {
            throw new \InvalidArgumentException('Fixture generation requires an even number of teams.');
        }

        $firstLegRounds = $this->circleMethodRounds($teamIds);
        $fixtures = [];
        $week = 1;

        foreach ($firstLegRounds as $round) {
            foreach ($round as $pair) {
                $fixtures[] = [
                    'week' => $week,
                    'home_team_id' => $pair['home'],
                    'away_team_id' => $pair['away'],
                ];
            }
            $week++;
        }

        foreach ($firstLegRounds as $round) {
            foreach ($round as $pair) {
                $fixtures[] = [
                    'week' => $week,
                    'home_team_id' => $pair['away'],
                    'away_team_id' => $pair['home'],
                ];
            }
            $week++;
        }

        return $fixtures;
    }

    /**
     * @param  array<int, int>  $teamIds
     * @return array<int, array<int, array{home: int, away: int}>>
     */
    private function circleMethodRounds(array $teamIds): array
    {
        $teams = $teamIds;
        $roundCount = count($teams) - 1;
        $rounds = [];

        for ($round = 0; $round < $roundCount; $round++) {
            $pairings = [];
            $half = (int) (count($teams) / 2);

            for ($i = 0; $i < $half; $i++) {
                $first = $teams[$i];
                $second = $teams[count($teams) - 1 - $i];

                if ($round % 2 === 0) {
                    $pairings[] = ['home' => $first, 'away' => $second];
                } else {
                    $pairings[] = ['home' => $second, 'away' => $first];
                }
            }

            $rounds[] = $pairings;

            $fixed = array_shift($teams);
            $last = array_pop($teams);
            array_unshift($teams, $last);
            array_unshift($teams, $fixed);
        }

        return $rounds;
    }
}
