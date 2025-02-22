<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ground extends Model
{
    protected $fillable = [
        "ground_id",
        "ground_name",
        "avg_score",
        "avg_wickets",
        "avg_first_ing_score",
        "avg_first_ing_wickets",
        "avg_second_ing_score",
        "avg_second_ing_wickets",
        "ground_support_for",
        "bowling_support_for",
    ];
}
