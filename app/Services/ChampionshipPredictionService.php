<?php

namespace App\Services;

use App\Models\FootballMatch;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Support\Collection;

class ChampionshipPredictionService
{
    private const MONTE_CARLO_ITERATIONS = 5000;

    public function __construct(
        private readonly MatchSimulationService $simulationService,
        private readonly LeagueTableService $tableService,
    ) {}

    /**
     * @param  Collection<int, Team>  $teams
     * @param  Collection<int, FootballMatch>  $allMatches
     * @return array<int, array{team_id: int, name: string, short_name: string, percentage: float}>|null
     */
    public function predict(Season $season, Collection $teams, Collection $allMatches): ?array
    {
        if (! $season->predictionsVisible()) {
            return null;
        }

        $playedMatches = $allMatches->filter(fn (FootballMatch $match) => $match->isPlayed());
        $standings = $this->tableService->calculate($teams, $playedMatches);
        $remainingMatches = $allMatches->filter(fn (FootballMatch $match) => ! $match->isPlayed());

        if ($remainingMatches->isEmpty()) {
            return $this->finalStandingsPercentages($standings);
        }

        $clinched = $this->detectMathematicalClinch($standings, $remainingMatches, $teams);

        if ($clinched !== null) {
            return $clinched;
        }

        return $this->monteCarloPredictions($teams, $allMatches, $remainingMatches);
    }

    /**
     * @param  array<int, array<string, mixed>>  $standings
     * @return array<int, array{team_id: int, name: string, short_name: string, percentage: float}>
     */
    private function finalStandingsPercentages(array $standings): array
    {
        $leaderPoints = $standings[0]['points'];
        $leaders = array_filter($standings, fn (array $row) => $row['points'] === $leaderPoints);

        if (count($leaders) === 1) {
            return array_map(function (array $row) use ($standings): array {
                return [
                    'team_id' => $row['team_id'],
                    'name' => $row['name'],
                    'short_name' => $row['short_name'],
                    'percentage' => $row['team_id'] === $standings[0]['team_id'] ? 100.0 : 0.0,
                ];
            }, $standings);
        }

        $share = round(100 / count($leaders), 1);

        return array_map(function (array $row) use ($leaders, $share): array {
            $isLeader = collect($leaders)->contains(fn (array $leader) => $leader['team_id'] === $row['team_id']);

            return [
                'team_id' => $row['team_id'],
                'name' => $row['name'],
                'short_name' => $row['short_name'],
                'percentage' => $isLeader ? $share : 0.0,
            ];
        }, $standings);
    }

    /**
     * @param  array<int, array<string, mixed>>  $standings
     * @param  Collection<int, FootballMatch>  $remainingMatches
     * @param  Collection<int, Team>  $teams
     * @return array<int, array{team_id: int, name: string, short_name: string, percentage: float}>|null
     */
    private function detectMathematicalClinch(
        array $standings,
        Collection $remainingMatches,
        Collection $teams,
    ): ?array {
        $leader = $standings[0];
        $leaderMinPoints = $leader['points'];

        $canAnyoneCatch = false;

        foreach (array_slice($standings, 1) as $challenger) {
            $challengerMaxTotal = $challenger['points'] + $this->maxPossiblePoints(
                $challenger['team_id'],
                $remainingMatches,
            );

            if ($challengerMaxTotal >= $leaderMinPoints) {
                $canAnyoneCatch = true;
                break;
            }
        }

        if (! $canAnyoneCatch) {
            return array_map(function (array $row) use ($leader): array {
                return [
                    'team_id' => $row['team_id'],
                    'name' => $row['name'],
                    'short_name' => $row['short_name'],
                    'percentage' => $row['team_id'] === $leader['team_id'] ? 100.0 : 0.0,
                ];
            }, $standings);
        }

        $remainingCount = $remainingMatches->count();
        if ($remainingCount <= 2) {
            $tight = $this->tightRacePrediction($standings, $remainingMatches, $teams);

            if ($tight !== null) {
                return $tight;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, FootballMatch>  $remainingMatches
     */
    private function maxPossiblePoints(int $teamId, Collection $remainingMatches): int
    {
        $remainingForTeam = $remainingMatches->filter(
            fn (FootballMatch $match) => $match->home_team_id === $teamId || $match->away_team_id === $teamId
        );

        return $remainingForTeam->count() * 3;
    }

    /**
     * @param  array<int, array<string, mixed>>  $standings
     * @param  Collection<int, FootballMatch>  $remainingMatches
     * @param  Collection<int, Team>  $teams
     * @return array<int, array{team_id: int, name: string, short_name: string, percentage: float}>|null
     */
    private function tightRacePrediction(
        array $standings,
        Collection $remainingMatches,
        Collection $teams,
    ): ?array {
        $topTwo = array_slice($standings, 0, 2);

        if ($topTwo[0]['points'] !== $topTwo[1]['points']) {
            return null;
        }

        $decidingMatch = $remainingMatches->first(function (FootballMatch $match) use ($topTwo) {
            $ids = [$match->home_team_id, $match->away_team_id];

            return in_array($topTwo[0]['team_id'], $ids, true)
                && in_array($topTwo[1]['team_id'], $ids, true);
        });

        if (! $decidingMatch) {
            return null;
        }

        $teamA = $teams->firstWhere('id', $topTwo[0]['team_id']);
        $teamB = $teams->firstWhere('id', $topTwo[1]['team_id']);

        if (! $teamA || ! $teamB) {
            return null;
        }

        $formA = $this->formScore($topTwo[0]);
        $formB = $this->formScore($topTwo[1]);
        $strengthA = $teamA->effectiveStrength($decidingMatch->home_team_id === $teamA->id);
        $strengthB = $teamB->effectiveStrength($decidingMatch->home_team_id === $teamB->id);

        $weightA = ($strengthA * 0.6) + ($formA * 0.4);
        $weightB = ($strengthB * 0.6) + ($formB * 0.4);
        $total = max($weightA + $weightB, 1);

        $percentA = round(($weightA / $total) * 100, 1);
        $percentB = round(100 - $percentA, 1);

        return array_map(function (array $row) use ($topTwo, $percentA, $percentB): array {
            $percentage = 0.0;

            if ($row['team_id'] === $topTwo[0]['team_id']) {
                $percentage = $percentA;
            } elseif ($row['team_id'] === $topTwo[1]['team_id']) {
                $percentage = $percentB;
            }

            return [
                'team_id' => $row['team_id'],
                'name' => $row['name'],
                'short_name' => $row['short_name'],
                'percentage' => $percentage,
            ];
        }, $standings);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function formScore(array $row): float
    {
        return ($row['goals_for'] * 1.5) + ($row['points'] * 2) + max($row['goal_difference'], 0);
    }

    /**
     * @param  Collection<int, Team>  $teams
     * @param  Collection<int, FootballMatch>  $allMatches
     * @param  Collection<int, FootballMatch>  $remainingMatches
     * @return array<int, array{team_id: int, name: string, short_name: string, percentage: float}>
     */
    private function monteCarloPredictions(
        Collection $teams,
        Collection $allMatches,
        Collection $remainingMatches,
    ): array {
        $wins = array_fill_keys($teams->pluck('id')->all(), 0);

        for ($i = 0; $i < self::MONTE_CARLO_ITERATIONS; $i++) {
            $simulated = $this->simulateRemaining($teams, $allMatches, $remainingMatches, $i + 1);
            $simulatedStandings = $this->tableService->calculate($teams, $simulated);
            $wins[$simulatedStandings[0]['team_id']]++;
        }

        return $teams->sortBy('name')->values()->map(function (Team $team) use ($wins): array {
            return [
                'team_id' => $team->id,
                'name' => $team->name,
                'short_name' => $team->short_name,
                'percentage' => round(($wins[$team->id] / self::MONTE_CARLO_ITERATIONS) * 100, 1),
            ];
        })->sortByDesc('percentage')->values()->all();
    }

    /**
     * @param  Collection<int, Team>  $teams
     * @param  Collection<int, FootballMatch>  $allMatches
     * @param  Collection<int, FootballMatch>  $remainingMatches
     * @return Collection<int, FootballMatch>
     */
    private function simulateRemaining(
        Collection $teams,
        Collection $allMatches,
        Collection $remainingMatches,
        int $seed,
    ): Collection {
        $simulator = $this->simulationService->withSeed($seed);
        $played = $allMatches->filter(fn (FootballMatch $match) => $match->isPlayed())->values();

        foreach ($remainingMatches as $match) {
            $home = $teams->firstWhere('id', $match->home_team_id);
            $away = $teams->firstWhere('id', $match->away_team_id);

            if (! $home || ! $away) {
                continue;
            }

            $result = $simulator->simulate($home, $away);
            $clone = clone $match;
            $clone->home_goals = $result['home_goals'];
            $clone->away_goals = $result['away_goals'];
            $clone->status = FootballMatch::STATUS_PLAYED;
            $played->push($clone);
        }

        return $played;
    }
}
