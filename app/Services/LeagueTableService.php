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
                'slug' => $team->slug,
                'logo_url' => $team->logoUrl(),
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
        usort($sorted, function (array $a, array $b) use ($playedMatches): int {
            return $this->compareRows($a, $b, $playedMatches);
        });

        foreach ($sorted as $index => &$row) {
            $row['position'] = $index + 1;
        }

        return $sorted;
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     * @param  Collection<int, FootballMatch>  $playedMatches
     */
    private function compareRows(array $a, array $b, Collection $playedMatches): int
    {
        $primary = [$b['points'], $b['goal_difference'], $b['goals_for']]
            <=> [$a['points'], $a['goal_difference'], $a['goals_for']];

        if ($primary !== 0) {
            return $primary;
        }

        $headToHead = $this->compareHeadToHead($a['team_id'], $b['team_id'], $playedMatches);

        if ($headToHead !== 0) {
            return $headToHead;
        }

        return $a['name'] <=> $b['name'];
    }

    /**
     * @param  Collection<int, FootballMatch>  $playedMatches
     */
    private function compareHeadToHead(int $teamAId, int $teamBId, Collection $playedMatches): int
    {
        $pointsA = 0;
        $pointsB = 0;
        $goalsA = 0;
        $goalsB = 0;

        foreach ($playedMatches as $match) {
            if (! $match->isPlayed()) {
                continue;
            }

            $isHeadToHead = ($match->home_team_id === $teamAId && $match->away_team_id === $teamBId)
                || ($match->home_team_id === $teamBId && $match->away_team_id === $teamAId);

            if (! $isHeadToHead) {
                continue;
            }

            $teamAGoals = $match->home_team_id === $teamAId ? $match->home_goals : $match->away_goals;
            $teamBGoals = $match->home_team_id === $teamBId ? $match->home_goals : $match->away_goals;

            $goalsA += $teamAGoals;
            $goalsB += $teamBGoals;

            if ($teamAGoals > $teamBGoals) {
                $pointsA += 3;
            } elseif ($teamBGoals > $teamAGoals) {
                $pointsB += 3;
            } else {
                $pointsA++;
                $pointsB++;
            }
        }

        return [$pointsB, $goalsB - $goalsA, $goalsB] <=> [$pointsA, $goalsA - $goalsB, $goalsA];
    }
}
