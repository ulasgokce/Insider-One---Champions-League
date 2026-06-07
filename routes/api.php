<?php

use App\Http\Controllers\Api\LeagueController;
use Illuminate\Support\Facades\Route;

Route::get('/teams', [LeagueController::class, 'teams']);

Route::prefix('league')->group(function () {
    Route::get('/state', [LeagueController::class, 'state']);
    Route::post('/start', [LeagueController::class, 'start']);
    Route::post('/configure-teams', [LeagueController::class, 'configureTeams']);
    Route::post('/play-week', [LeagueController::class, 'playWeek']);
    Route::post('/next-week', [LeagueController::class, 'nextWeek']);
    Route::post('/play-all', [LeagueController::class, 'playAll']);
});

Route::post('/matches/{match}/play', [LeagueController::class, 'playMatch']);
Route::patch('/matches/{match}', [LeagueController::class, 'updateMatch']);
