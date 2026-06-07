<?php

namespace App\Models;

use App\Services\TeamPoolService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'id',
        'slug',
        'name',
        'short_name',
        'country',
        'power_rating',
        'home_advantage',
        'supporter_strength',
        'goalkeeper_factor',
    ];

    public function homeMatches(): HasMany
    {
        return $this->hasMany(FootballMatch::class, 'home_team_id');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(FootballMatch::class, 'away_team_id');
    }

    public function logoUrl(): string
    {
        return app(TeamPoolService::class)->logoUrlForSlug($this->slug);
    }

    public function effectiveStrength(bool $isHome): float
    {
        $strength = (float) $this->power_rating
            + $this->supporter_strength
            + $this->goalkeeper_factor;

        if ($isHome) {
            $strength += $this->home_advantage;
        }

        return $strength;
    }
}
