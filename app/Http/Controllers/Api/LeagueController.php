<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FootballMatch;
use App\Services\LeagueSeasonService;
use App\Services\TeamPoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    public function __construct(
        private readonly LeagueSeasonService $leagueSeasonService,
        private readonly TeamPoolService $teamPoolService,
    ) {}

    public function state(): JsonResponse
    {
        return response()->json($this->leagueSeasonService->buildState());
    }

    public function teams(): JsonResponse
    {
        $teams = $this->teamPoolService->ensurePool();

        return response()->json([
            'teams' => $teams->map(fn ($team) => $this->teamPoolService->formatTeam($team))->values(),
            'default_slugs' => TeamPoolService::DEFAULT_SLUGS,
            'default_team_ids' => $this->teamPoolService->defaultTeamIds(),
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'team_ids' => ['sometimes', 'array', 'size:4'],
            'team_ids.*' => ['integer', 'exists:teams,id'],
        ]);

        try {
            $season = $this->leagueSeasonService->startNewSeason($validated['team_ids'] ?? null);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($this->leagueSeasonService->buildState($season));
    }

    public function configureTeams(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'team_ids' => ['required', 'array', 'size:4'],
            'team_ids.*' => ['integer', 'distinct', 'exists:teams,id'],
        ]);

        try {
            $season = $this->leagueSeasonService->startNewSeason($validated['team_ids']);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($this->leagueSeasonService->buildState($season));
    }

    public function playMatch(FootballMatch $match): JsonResponse
    {
        try {
            $this->leagueSeasonService->playMatch($match);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($this->leagueSeasonService->buildState());
    }

    public function playWeek(): JsonResponse
    {
        $season = $this->leagueSeasonService->getOrCreateActiveSeason();
        $this->leagueSeasonService->playCurrentWeek($season);

        return response()->json($this->leagueSeasonService->buildState());
    }

    public function nextWeek(): JsonResponse
    {
        $season = $this->leagueSeasonService->getOrCreateActiveSeason();

        try {
            $this->leagueSeasonService->advanceWeek($season);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($this->leagueSeasonService->buildState());
    }

    public function playAll(): JsonResponse
    {
        $season = $this->leagueSeasonService->getOrCreateActiveSeason();
        $results = $this->leagueSeasonService->playAll($season);
        $state = $this->leagueSeasonService->buildState($season->fresh());

        return response()->json([
            ...$state,
            'play_all_results' => $results,
        ]);
    }

    public function updateMatch(Request $request, FootballMatch $match): JsonResponse
    {
        $validated = $request->validate([
            'home_goals' => ['required', 'integer', 'min:0', 'max:20'],
            'away_goals' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        try {
            $this->leagueSeasonService->updateMatchScore(
                $match,
                $validated['home_goals'],
                $validated['away_goals'],
            );
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json($this->leagueSeasonService->buildState());
    }
}
