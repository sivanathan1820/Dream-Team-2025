<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamStats extends Model
{
    protected $fillable = [
        'match_no',
        'dream_team_id',
        'total_dream_players',
        'dream_points',
        'players_stats',
    ];
}
