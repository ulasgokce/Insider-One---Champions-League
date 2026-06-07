<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_FINISHED = 'finished';

    protected $fillable = [
        'current_week',
        'total_weeks',
        'status',
    ];

    public function matches(): HasMany
    {
        return $this->hasMany(FootballMatch::class);
    }

    public function predictionsVisible(): bool
    {
        return $this->current_week >= ($this->total_weeks - 2);
    }

    public function isFinished(): bool
    {
        return $this->status === self::STATUS_FINISHED;
    }
}
