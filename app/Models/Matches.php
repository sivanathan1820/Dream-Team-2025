<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matches extends Model
{
    protected $fillable = [
        "match_no",
        "match_at",
        "match_name",
        "team_1",
        "team_2",
        "ground_id",
    ];
}
