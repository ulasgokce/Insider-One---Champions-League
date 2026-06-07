<?php

namespace Database\Seeders;

use App\Services\LeagueSeasonService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(LeagueSeasonService::class)->startNewSeason();
    }
}
