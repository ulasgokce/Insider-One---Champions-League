<?php

namespace App\Services;

use App\Models\FootballMatch;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeagueSeasonService
{
    public function __construct(
        private readonly FixtureGeneratorService $fixtureGenerator,
        private readonly MatchSimulationService $simulationService,
        private readonly LeagueTableService $tableService,
        private readonly ChampionshipPredictionService $predictionService,
        private readonly TeamPoolService $teamPoolService,
    ) {}

    public function getOrCreateActiveSeason(): Season
    {
        $this->teamPoolService->ensurePool();

        $season = Season::query()->latest()->first();

        if ($season) {
            return $season;
        }

        return $this->startNewSeason();
    }

    /**
     * @param  list<int>|null  $teamIds
     */
    public function startNewSeason(?array $teamIds = null): Season
    {
        $this->teamPoolService->ensurePool();

        $teams = $teamIds
            ? $this->teamPoolService->resolveSelection($teamIds)
            : $this->teamPoolService->defaultSelection();

        return DB::transaction(function () use ($teams) {
            FootballMatch::query()->delete();
            Season::query()->delete();

            $fixtures = $this->fixtureGenerator->generate($teams);

            $season = Season::query()->create([
                'current_week' => 1,
                'total_weeks' => max(array_column($fixtures, 'week')),
                'status' => Season::STATUS_ACTIVE,
            ]);

            foreach ($fixtures as $fixture) {
                FootballMatch::query()->create([
                    'season_id' => $season->id,
                    ...$fixture,
                    'status' => FootballMatch::STATUS_SCHEDULED,
                ]);
            }

            return $season->fresh();
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function buildState(?Season $season = null): array
    {
        $this->teamPoolService->ensurePool();

        $season = $season ?? $this->getOrCreateActiveSeason();
        $matches = $season->matches()->with(['homeTeam', 'awayTeam'])->orderBy('week')->orderBy('id')->get();
        $teamIds = $matches->flatMap(fn (FootballMatch $match) => [$match->home_team_id, $match->away_team_id])->unique();
        $teams = Team::query()->whereIn('id', $teamIds)->orderBy('name')->get();
        $playedMatches = $matches->filter(fn (FootballMatch $match) => $match->isPlayed());
        $standings = $this->tableService->calculate($teams, $playedMatches);
        $currentWeekMatches = $matches->where('week', $season->current_week)->values();
        $predictions = $this->predictionService->predict($season, $teams, $matches);
        $allTeams = Team::query()->orderBy('name')->get();

        $champion = null;
        if ($season->isFinished() && count($standings) > 0) {
            $champion = $standings[0];
        }

        return [
            'season' => [
                'id' => $season->id,
                'current_week' => $season->current_week,
                'total_weeks' => $season->total_weeks,
                'status' => $season->status,
                'predictions_visible' => $season->predictionsVisible(),
                'current_week_complete' => $currentWeekMatches->every(fn (FootballMatch $match) => $match->isPlayed()),
                'has_unplayed_matches' => $matches->contains(fn (FootballMatch $match) => ! $match->isPlayed()),
                'is_finished' => $season->isFinished(),
            ],
            'standings' => $standings,
            'current_week_fixtures' => $currentWeekMatches->map(fn (FootballMatch $match) => $this->formatMatch($match))->values(),
            'predictions' => $predictions,
            'results_by_week' => $this->groupResultsByWeek($matches),
            'champion' => $champion,
            'all_teams' => $allTeams->map(fn (Team $team) => $this->teamPoolService->formatTeam($team))->values(),
            'selected_team_ids' => $teamIds->values()->all(),
            'default_team_ids' => $this->teamPoolService->defaultTeamIds(),
        ];
    }

    public function playMatch(FootballMatch $match): FootballMatch
    {
        if ($match->isPlayed()) {
            throw new \RuntimeException('This match has already been played.');
        }

        $result = $this->simulationService->simulate($match->homeTeam, $match->awayTeam);

        $match->update([
            'home_goals' => $result['home_goals'],
            'away_goals' => $result['away_goals'],
            'status' => FootballMatch::STATUS_PLAYED,
            'played_at' => now(),
        ]);

        $this->refreshSeasonStatus($match->season);

        return $match->fresh(['homeTeam', 'awayTeam']);
    }

    public function playCurrentWeek(Season $season): Season
    {
        $matches = $season->matches()
            ->with(['homeTeam', 'awayTeam'])
            ->where('week', $season->current_week)
            ->where('status', FootballMatch::STATUS_SCHEDULED)
            ->get();

        foreach ($matches as $match) {
            $this->playMatch($match);
        }

        return $season->fresh();
    }

    public function advanceWeek(Season $season): Season
    {
        $currentWeekMatches = $season->matches()->where('week', $season->current_week)->get();

        if ($currentWeekMatches->contains(fn (FootballMatch $match) => ! $match->isPlayed())) {
            throw new \RuntimeException('All matches in the current week must be played before advancing.');
        }

        if ($season->current_week >= $season->total_weeks) {
            $season->update(['status' => Season::STATUS_FINISHED]);

            return $season->fresh();
        }

        $season->update(['current_week' => $season->current_week + 1]);
        $this->refreshSeasonStatus($season);

        return $season->fresh();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function playAll(Season $season): array
    {
        $matches = $season->matches()->with(['homeTeam', 'awayTeam'])->orderBy('week')->orderBy('id')->get();

        foreach ($matches as $match) {
            if (! $match->isPlayed()) {
                $this->playMatch($match);
            }
        }

        $season->update([
            'current_week' => $season->total_weeks,
            'status' => Season::STATUS_FINISHED,
        ]);

        return $this->groupResultsByWeek($season->matches()->with(['homeTeam', 'awayTeam'])->orderBy('week')->get());
    }

    public function updateMatchScore(FootballMatch $match, int $homeGoals, int $awayGoals): FootballMatch
    {
        $season = $match->season;

        if ($season->status === Season::STATUS_FINISHED) {
            throw new \InvalidArgumentException('Cannot edit scores after the season has finished.');
        }

        $match->update([
            'home_goals' => $homeGoals,
            'away_goals' => $awayGoals,
            'status' => FootballMatch::STATUS_PLAYED,
            'played_at' => $match->played_at ?? now(),
        ]);

        $this->refreshSeasonStatus($match->season);

        return $match->fresh(['homeTeam', 'awayTeam']);
    }

    private function refreshSeasonStatus(Season $season): void
    {
        $season->refresh();

        $hasUnplayed = $season->matches()->where('status', FootballMatch::STATUS_SCHEDULED)->exists();

        if (! $hasUnplayed) {
            $season->update([
                'status' => Season::STATUS_FINISHED,
                'current_week' => $season->total_weeks,
            ]);
        } elseif ($season->status === Season::STATUS_FINISHED) {
            $season->update(['status' => Season::STATUS_ACTIVE]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formatMatch(FootballMatch $match): array
    {
        return [
            'id' => $match->id,
            'week' => $match->week,
            'status' => $match->status,
            'home_team' => $this->formatTeam($match->homeTeam),
            'away_team' => $this->formatTeam($match->awayTeam),
            'home_goals' => $match->home_goals,
            'away_goals' => $match->away_goals,
            'played_at' => $match->played_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatTeam(Team $team): array
    {
        return [
            'id' => $team->id,
            'slug' => $team->slug,
            'name' => $team->name,
            'short_name' => $team->short_name,
            'logo_url' => $team->logoUrl(),
        ];
    }

    /**
     * @param  Collection<int, FootballMatch>  $matches
     * @return array<int, array<string, mixed>>
     */
    private function groupResultsByWeek(Collection $matches): array
    {
        return $matches
            ->groupBy('week')
            ->sortKeys()
            ->map(function (Collection $weekMatches, int $week) {
                return [
                    'week' => $week,
                    'matches' => $weekMatches->map(fn (FootballMatch $match) => $this->formatMatch($match))->values(),
                ];
            })
            ->values()
            ->all();
    }
}
