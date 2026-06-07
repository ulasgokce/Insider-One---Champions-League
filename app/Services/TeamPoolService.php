<?php

namespace App\Services;

use App\Models\Team;
use Illuminate\Support\Collection;

class TeamPoolService
{
    /** @var list<string> */
    public const DEFAULT_SLUGS = ['man-city', 'real-madrid', 'chelsea', 'galatasaray'];

    /** @var array<string, string> */
    private const LOGO_FILES = [
        'man-city' => 'man-city.png',
        'real-madrid' => 'real-madrid.png',
        'chelsea' => 'chelsea.png',
        'galatasaray' => 'galatasaray.png',
        'fenerbahce' => 'fenerbahce.webp',
        'arsenal' => 'arsenal.png',
        'bayern' => 'bayern.png',
        'barcelona' => 'barcelona.png',
        'liverpool' => 'liverpool.png',
        'psg' => 'psg.png',
    ];

    /**
     * @return Collection<int, Team>
     */
    public function ensurePool(): Collection
    {
        $knownSlugs = collect($this->definitions())->pluck('slug');

        Team::query()
            ->where(function ($query) use ($knownSlugs) {
                $query->whereNull('slug')
                    ->orWhereNotIn('slug', $knownSlugs);
            })
            ->delete();

        foreach ($this->definitions() as $definition) {
            Team::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                $definition,
            );
        }

        return Team::query()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Team>
     */
    public function defaultSelection(): Collection
    {
        return Team::query()
            ->whereIn('slug', self::DEFAULT_SLUGS)
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  list<int>  $teamIds
     * @return Collection<int, Team>
     */
    public function resolveSelection(array $teamIds): Collection
    {
        if (count($teamIds) !== 4) {
            throw new \InvalidArgumentException('Exactly four teams must be selected.');
        }

        $teams = Team::query()->whereIn('id', $teamIds)->get();

        if ($teams->count() !== 4) {
            throw new \InvalidArgumentException('One or more selected teams were not found.');
        }

        return $teams->sortBy('name')->values();
    }

    /**
     * @return list<int>
     */
    public function defaultTeamIds(): array
    {
        return $this->defaultSelection()->pluck('id')->values()->all();
    }

    public function logoUrlForSlug(?string $slug): string
    {
        if (! $slug) {
            return '/logos/default.png';
        }

        $file = self::LOGO_FILES[$slug] ?? $slug.'.png';

        return '/logos/'.$file;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatTeam(Team $team): array
    {
        return [
            'id' => $team->id,
            'slug' => $team->slug,
            'name' => $team->name,
            'short_name' => $team->short_name,
            'country' => $team->country,
            'logo_url' => $this->logoUrlForSlug($team->slug),
            'power_rating' => $team->power_rating,
            'is_default' => in_array($team->slug, self::DEFAULT_SLUGS, true),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function definitions(): array
    {
        return [
            ['slug' => 'man-city', 'name' => 'Manchester City', 'short_name' => 'Man City', 'country' => 'ENG', 'power_rating' => 95, 'home_advantage' => 8, 'supporter_strength' => 9, 'goalkeeper_factor' => 9],
            ['slug' => 'real-madrid', 'name' => 'Real Madrid', 'short_name' => 'Real Madrid', 'country' => 'ESP', 'power_rating' => 90, 'home_advantage' => 9, 'supporter_strength' => 10, 'goalkeeper_factor' => 8],
            ['slug' => 'chelsea', 'name' => 'Chelsea', 'short_name' => 'Chelsea', 'country' => 'ENG', 'power_rating' => 75, 'home_advantage' => 6, 'supporter_strength' => 7, 'goalkeeper_factor' => 7],
            ['slug' => 'galatasaray', 'name' => 'Galatasaray', 'short_name' => 'Galatasaray', 'country' => 'TUR', 'power_rating' => 55, 'home_advantage' => 10, 'supporter_strength' => 9, 'goalkeeper_factor' => 5],
            ['slug' => 'fenerbahce', 'name' => 'Fenerbahce', 'short_name' => 'Fenerbahce', 'country' => 'TUR', 'power_rating' => 58, 'home_advantage' => 10, 'supporter_strength' => 10, 'goalkeeper_factor' => 6],
            ['slug' => 'arsenal', 'name' => 'Arsenal', 'short_name' => 'Arsenal', 'country' => 'ENG', 'power_rating' => 88, 'home_advantage' => 7, 'supporter_strength' => 8, 'goalkeeper_factor' => 8],
            ['slug' => 'bayern', 'name' => 'Bayern Munich', 'short_name' => 'Bayern', 'country' => 'GER', 'power_rating' => 92, 'home_advantage' => 8, 'supporter_strength' => 9, 'goalkeeper_factor' => 9],
            ['slug' => 'barcelona', 'name' => 'Barcelona', 'short_name' => 'Barcelona', 'country' => 'ESP', 'power_rating' => 87, 'home_advantage' => 8, 'supporter_strength' => 9, 'goalkeeper_factor' => 7],
            ['slug' => 'liverpool', 'name' => 'Liverpool', 'short_name' => 'Liverpool', 'country' => 'ENG', 'power_rating' => 89, 'home_advantage' => 7, 'supporter_strength' => 10, 'goalkeeper_factor' => 8],
            ['slug' => 'psg', 'name' => 'Paris Saint-Germain', 'short_name' => 'PSG', 'country' => 'FRA', 'power_rating' => 86, 'home_advantage' => 7, 'supporter_strength' => 8, 'goalkeeper_factor' => 7],
        ];
    }
}
