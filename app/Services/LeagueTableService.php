<?php

namespace App\Services;

use App\Models\FootballMatch;
use App\Models\Team;
use Illuminate\Support\Collection;

class LeagueTableService
{
    /**
     * @param  Collection<int, Team>  $teams
     * @param  Collection<int, FootballMatch>  $playedMatches
     * @return array<int, array<string, mixed>>
     */
    public function calculate(Collection $teams, Collection $playedMatches): array
    {
        $rows = [];

        foreach ($teams as $team) {
            $rows[$team->id] = [
                'team_id' => $team->id,
                'name' => $team->name,
                'short_name' => $team->short_name,
                'country' => $team->country,
                'played' => 0,
                'won' => 0,
                'drawn' => 0,
                'lost' => 0,
                'goals_for' => 0,
                'goals_against' => 0,
                'goal_difference' => 0,
                'points' => 0,
            ];
        }

        foreach ($playedMatches as $match) {
            if (! $match->isPlayed()) {
                continue;
            }

            $home = &$rows[$match->home_team_id];
            $away = &$rows[$match->away_team_id];

            $home['played']++;
            $away['played']++;
            $home['goals_for'] += $match->home_goals;
            $home['goals_against'] += $match->away_goals;
            $away['goals_for'] += $match->away_goals;
            $away['goals_against'] += $match->home_goals;

            if ($match->home_goals > $match->away_goals) {
                $home['won']++;
                $home['points'] += 3;
                $away['lost']++;
            } elseif ($match->home_goals < $match->away_goals) {
                $away['won']++;
                $away['points'] += 3;
                $home['lost']++;
            } else {
                $home['drawn']++;
                $away['drawn']++;
                $home['points']++;
                $away['points']++;
            }
        }

        foreach ($rows as &$row) {
            $row['goal_difference'] = $row['goals_for'] - $row['goals_against'];
        }

        $sorted = array_values($rows);
        usort($sorted, function (array $a, array $b): int {
            return $this->compareRows($a, $b);
        });

        foreach ($sorted as $index => &$row) {
            $row['position'] = $index + 1;
        }

        return $sorted;
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    private function compareRows(array $a, array $b): int
    {
        return [$b['points'], $b['goal_difference'], $b['goals_for'], $a['name']]
            <=> [$a['points'], $a['goal_difference'], $a['goals_for'], $b['name']];
    }
}
