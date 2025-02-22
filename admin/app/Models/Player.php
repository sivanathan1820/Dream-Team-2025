<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = [
        "team_id",
        "name",
        "role",
        "position",
        "credit_score",
        "strong",
        "weak",
        "trustable",
    ];
}
