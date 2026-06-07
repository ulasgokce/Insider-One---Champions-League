<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FootballMatch extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PLAYED = 'played';

    protected $table = 'matches';

    protected $fillable = [
        'season_id',
        'week',
        'home_team_id',
        'away_team_id',
        'home_goals',
        'away_goals',
        'status',
        'played_at',
    ];

    protected function casts(): array
    {
        return [
            'played_at' => 'datetime',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function isPlayed(): bool
    {
        return $this->status === self::STATUS_PLAYED;
    }
}
