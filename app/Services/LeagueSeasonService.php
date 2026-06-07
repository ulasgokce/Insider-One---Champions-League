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
    ) {}

    public function getOrCreateActiveSeason(): Season
    {
        $season = Season::query()->latest()->first();

        if ($season) {
            return $season;
        }

        return $this->startNewSeason();
    }

    public function startNewSeason(): Season
    {
        return DB::transaction(function () {
            FootballMatch::query()->delete();
            Season::query()->delete();

            $teams = $this->seedTeams();
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
     * @return Collection<int, Team>
     */
    private function seedTeams(): Collection
    {
        Team::query()->delete();

        $definitions = [
            ['name' => 'Manchester City', 'short_name' => 'Man City', 'country' => 'ENG', 'power_rating' => 95, 'home_advantage' => 8, 'supporter_strength' => 9, 'goalkeeper_factor' => 9],
            ['name' => 'Real Madrid', 'short_name' => 'Real Madrid', 'country' => 'ESP', 'power_rating' => 90, 'home_advantage' => 9, 'supporter_strength' => 10, 'goalkeeper_factor' => 8],
            ['name' => 'Chelsea', 'short_name' => 'Chelsea', 'country' => 'ENG', 'power_rating' => 75, 'home_advantage' => 6, 'supporter_strength' => 7, 'goalkeeper_factor' => 7],
            ['name' => 'Galatasaray', 'short_name' => 'Galatasaray', 'country' => 'TUR', 'power_rating' => 55, 'home_advantage' => 10, 'supporter_strength' => 9, 'goalkeeper_factor' => 5],
        ];

        foreach ($definitions as $definition) {
            Team::query()->create($definition);
        }

        return Team::query()->orderBy('name')->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildState(?Season $season = null): array
    {
        $season = $season ?? $this->getOrCreateActiveSeason();
        $teams = Team::query()->orderBy('name')->get();
        $matches = $season->matches()->with(['homeTeam', 'awayTeam'])->orderBy('week')->orderBy('id')->get();
        $playedMatches = $matches->filter(fn (FootballMatch $match) => $match->isPlayed());
        $standings = $this->tableService->calculate($teams, $playedMatches);
        $currentWeekMatches = $matches->where('week', $season->current_week)->values();
        $predictions = $this->predictionService->predict($season, $teams, $matches);

        return [
            'season' => [
                'id' => $season->id,
                'current_week' => $season->current_week,
                'total_weeks' => $season->total_weeks,
                'status' => $season->status,
                'predictions_visible' => $season->predictionsVisible(),
                'current_week_complete' => $currentWeekMatches->every(fn (FootballMatch $match) => $match->isPlayed()),
                'has_unplayed_matches' => $matches->contains(fn (FootballMatch $match) => ! $match->isPlayed()),
            ],
            'standings' => $standings,
            'current_week_fixtures' => $currentWeekMatches->map(fn (FootballMatch $match) => $this->formatMatch($match))->values(),
            'predictions' => $predictions,
            'results_by_week' => $this->groupResultsByWeek($matches),
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
            'home_team' => [
                'id' => $match->homeTeam->id,
                'name' => $match->homeTeam->name,
                'short_name' => $match->homeTeam->short_name,
            ],
            'away_team' => [
                'id' => $match->awayTeam->id,
                'name' => $match->awayTeam->name,
                'short_name' => $match->awayTeam->short_name,
            ],
            'home_goals' => $match->home_goals,
            'away_goals' => $match->away_goals,
            'played_at' => $match->played_at?->toIso8601String(),
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
