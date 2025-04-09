<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Ground;
use App\Models\Matches;
use App\Models\Player;
use App\Models\Team;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        # Ground Seeding
        $groundData = file_get_contents(public_path('data/grounds.json'));
        if ($groundData === false) {
            echo "Failed to read the file.";
            return;
        }
        $response = json_decode($groundData, true);
        if ($response === null) {
            echo "Failed to decode JSON.";
            return;
        }
        foreach ($response as $value) {
            Ground::create([
                "ground_id" => $value["ground_id"],
                "ground_name" => $value["ground_name"],
                "avg_score" => $value["avg_score"],
                "avg_wickets" => $value["avg_wickets"],
                "avg_first_ing_score" => $value["avg_first_ing_score"],
                "avg_first_ing_wickets" => $value["avg_first_ing_wickets"],
                "avg_second_ing_score" => $value["avg_second_ing_score"],
                "avg_second_ing_wickets" => $value["avg_second_ing_wickets"],
                "ground_support_for" => $value["ground_support_for"],
                "bowling_support_for" => $value["bowling_support_for"],
            ]);
        }

        # Match Seeding
        $matchData = file_get_contents(public_path('data/matches.json'));
        if ($matchData === false) {
            echo "Failed to read the file.";
            return;
        }
        $response = json_decode($matchData, true);
        if ($response === null) {
            echo "Failed to decode JSON.";
            return;
        }
        foreach ($response as $value) {
            Matches::create([
                "match_no" => $value["match_no"],
                "match_at" => $value["match_at"],
                "match_name" => $value["match_name"],
                "team_1" => $value["team_1"],
                "team_2" => $value["team_2"],
                "ground_id" => $value["ground_id"],
            ]);
        }

        # Player Seeding
        $playerData = file_get_contents(public_path('data/players.json'));
        if ($playerData === false) {
            echo "Failed to read the file.";
            return;
        }
        $response = json_decode($playerData, true);
        if ($response === null) {
            echo "Failed to decode JSON.";
            return;
        }
        foreach ($response as $value) {
            Player::create([
                "team_id" => $value["team_id"],
                "name" => $value["name"],
                "role" => $value["role"],
                "position" => $value["position"],
                "credit_score" => $value["credit_score"],
                "strong" => $value["strong"],
                "weak" => $value["weak"],
                "trustable" => $value["trustable"],
            ]);
        }

        # Team Seeding
        $teamData = file_get_contents(public_path('data/teams.json'));
        if ($teamData === false) {
            echo "Failed to read the file.";
            return;
        }
        $response = json_decode($teamData, true);
        if ($response === null) {
            echo "Failed to decode JSON.";
            return;
        }
        foreach ($response as $value) {
            Team::create([
                "team_id" => $value["uuid"],
                "team_short" => $value["team_id"],
                "team_name" => $value["team_name"],
            ]);
        }
    }
}
