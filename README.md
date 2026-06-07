# Insider One Champions League Simulator

A Laravel + Vue 3 Champions League group-stage simulator with realistic match outcomes, league standings, and championship predictions.

## Live Demo

Deploy to [Render.com](https://render.com) using the included `render.yaml` blueprint, then add your live URL here:

`https://your-service.onrender.com`

## GitHub Repository

https://github.com/ulasgokce/Insider-One---Champions-League

## Features

- 4-team double round-robin (6 weeks, 12 matches)
- Realistic Poisson-based match simulation using team power, home advantage, supporter strength, and goalkeeper factors
- Week-by-week simulation with per-match play or play entire week
- Championship predictions from Week 4 (last 3 weeks) with Monte Carlo simulation and mathematical clinch detection
- Play All season shortcut and manual score editing
- Mobile-first responsive UI with dark/light mode and smooth transitions
- SQLite database (no separate DB hosting required)

## Tech Stack

- **Backend:** Laravel 13 (PHP 8.3), strict OOP service layer
- **Frontend:** Vue 3 + Vite + Tailwind CSS v4
- **Database:** SQLite
- **Tests:** PHPUnit
- **Deployment:** Render.com (Docker)

## Local Setup

```bash
cd Insider-One---Champions-League
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
npm install
npm run build
php artisan migrate
php artisan db:seed
php artisan serve
```

Visit `http://127.0.0.1:8000`

For development with hot reload:

```bash
npm run dev
php artisan serve
```

## Running Tests

```bash
php artisan test
```

## API Endpoints

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/api/league/state` | Current league state |
| POST | `/api/league/start` | Reset season |
| POST | `/api/matches/{id}/play` | Simulate one match |
| POST | `/api/league/play-week` | Simulate current week |
| POST | `/api/league/next-week` | Advance to next week |
| POST | `/api/league/play-all` | Simulate entire season |
| PATCH | `/api/matches/{id}` | Edit match score |

## Architecture

Business logic lives in dedicated services under `app/Services/`:

- `FixtureGeneratorService` — round-robin schedule generation
- `MatchSimulationService` — Poisson goal simulation
- `LeagueTableService` — Premier League scoring and tie-breakers
- `ChampionshipPredictionService` — title probability estimation
- `LeagueSeasonService` — season orchestration

## Deploy to Render

1. Push this repo to GitHub
2. In Render: **New → Blueprint** → connect the repository
3. Set `APP_URL` to your Render service URL
4. Deploy — migrations run automatically on boot

Note: Render free tier uses ephemeral storage; league progress may reset on redeploy. Use **Reset Season** or replay from Week 1.

## License

MIT
