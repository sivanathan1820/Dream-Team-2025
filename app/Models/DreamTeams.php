<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DreamTeams extends Model
{
    protected $fillable = [
        "match_no",
        "dream_id",
        "players",
        "captain_id",
        "vice_captain_id",
        "total_credit",
        "team_position"
    ];
}
