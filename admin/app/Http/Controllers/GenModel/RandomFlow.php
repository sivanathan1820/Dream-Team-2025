<?php

namespace App\Http\Controllers\GenModel;

use App\Http\Controllers\Controller;
use App\Models\Ground;
use App\Models\Matches;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\Request;

class RandomFlow extends Controller
{
    public function matchDetails($match_no = "match_1")
    {
        // Validate the match_no input
        if (!$match_no) {
            return response()->json(['error' => 'Match number is required.'], 400);
        }

        // Fetch the match details
        $matchDetails = Matches::where('match_no', $match_no)->first();

        // Check if the match exists
        if (!$matchDetails) {
            return response()->json(['error' => 'Match not found.'], 404);
        }

        // Initialize player arrays
        $wicketKeepers = [];
        $batters = [];
        $allrounders = [];
        $bowlers = [];
        $groundData = null;

        // Check if team 1 and team 2 are assigned and fetch players
        if ($matchDetails->team_1 && $matchDetails->team_2) {
            // For Team 1
            $wicketKeepers = Player::where('role', 'Wicket Keeper')->where('team_id', $matchDetails->team_1)->get();
            if ($wicketKeepers->isEmpty()) {
                return response()->json(['error' => 'No Wicket Keeper found for Team 1.'], 404);
            }

            $batters = Player::where('role', 'Batter')->where('team_id', $matchDetails->team_1)->get();
            if ($batters->isEmpty()) {
                return response()->json(['error' => 'No Batters found for Team 1.'], 404);
            }

            $allrounders = Player::where('role', 'All Rounder')->where('team_id', $matchDetails->team_1)->get();
            if ($allrounders->isEmpty()) {
                return response()->json(['error' => 'No Allrounders found for Team 1.'], 404);
            }

            $bowlers = Player::where('role', 'Bowler')->where('team_id', $matchDetails->team_1)->get();
            if ($bowlers->isEmpty()) {
                return response()->json(['error' => 'No Bowlers found for Team 1.'], 404);
            }

            // For Team 2
            $team2WicketKeepers = Player::where('role', 'Wicket Keeper')->where('team_id', $matchDetails->team_2)->get();
            if ($team2WicketKeepers->isEmpty()) {
                return response()->json(['error' => 'No Wicket Keeper found for Team 2.'], 404);
            }

            $team2Batters = Player::where('role', 'Batter')->where('team_id', $matchDetails->team_2)->get();
            if ($team2Batters->isEmpty()) {
                return response()->json(['error' => 'No Batters found for Team 2.'], 404);
            }

            $team2Allrounders = Player::where('role', 'All Rounder')->where('team_id', $matchDetails->team_2)->get();
            if ($team2Allrounders->isEmpty()) {
                return response()->json(['error' => 'No Allrounders found for Team 2.'], 404);
            }

            $team2Bowlers = Player::where('role', 'Bowler')->where('team_id', $matchDetails->team_2)->get();
            if ($team2Bowlers->isEmpty()) {
                return response()->json(['error' => 'No Bowlers found for Team 2.'], 404);
            }
        } else {
            return response()->json(['error' => 'Both Team 1 and Team 2 must be assigned.'], 404);
        }

        // Check if ground exists and fetch ground data
        if ($matchDetails->ground_id) {
            $groundData = Ground::where('ground_id', $matchDetails->ground_id)->first();
            if (!$groundData) {
                return response()->json(['error' => 'Ground not found.'], 404);
            }
        } else {
            return response()->json(['error' => 'Ground not assigned.'], 404);
        }

        // Return the match details along with players and ground info
        return $this->generateTeam([
            'match' => $matchDetails,
            'team_1' => [
                'wicketKeepers' => $wicketKeepers,
                'batters' => $batters,
                'allrounders' => $allrounders,
                'bowlers' => $bowlers,
            ],
            'team_2' => [
                'wicketKeepers' => $team2WicketKeepers,
                'batters' => $team2Batters,
                'allrounders' => $team2Allrounders,
                'bowlers' => $team2Bowlers,
            ],
            'ground' => $groundData,
        ]);
    }

    public function generateTeam($details = [])
    {
        // Merge players from both teams
        $wicketKeepers = array_merge(
            $details['team_1']['wicketKeepers']->toArray(),
            $details['team_2']['wicketKeepers']->toArray()
        );

        $batters = array_merge(
            $details['team_1']['batters']->toArray(),
            $details['team_2']['batters']->toArray()
        );

        $allrounders = array_merge(
            $details['team_1']['allrounders']->toArray(),
            $details['team_2']['allrounders']->toArray()
        );

        $bowlers = array_merge(
            $details['team_1']['bowlers']->toArray(),
            $details['team_2']['bowlers']->toArray()
        );

        // Position details
        $positionDetails = [
            "wicketKeepers" => $wicketKeepers,
            "batters" => $batters,
            "allrounders" => $allrounders,
            "bowlers" => $bowlers,
        ];
        
        // Config settings
        $expectedTeams = config("constant.expectedTeams.RandomFlow");
        $maxCreditScore = config("constant.maxCreditScore");
        $playersPerTeam = config("constant.playersPerTeam");
        $wicketKeepers_limit = config("constant.players_limit.wicketKeepers");
        $batters_limit = config("constant.players_limit.batters");
        $allrounders_limit = config("constant.players_limit.allrounders");
        $bowlers_limit = config("constant.players_limit.bowlers");

        $generatedTeamsCount = 0;
        $generatedTeams = [];
        
        while ($generatedTeamsCount < $expectedTeams) {
            $selectedPlayers = [
                "wicketKeepers" => [],
                "batters" => [],
                "allrounders" => [],
                "bowlers" => []
            ];

            // Select players for the team
            while (array_sum(array_map('count', $selectedPlayers)) < $playersPerTeam) {
                $randomIndex = rand(0, 3);
                $positionKeys = array_keys($positionDetails);
                $selectedPosition = $positionKeys[$randomIndex];
                $availablePlayers = $positionDetails[$selectedPosition];

                // Limit checking for each position
                $limit = ${$selectedPosition . "_limit"};

                if (count($selectedPlayers[$selectedPosition]) < $limit) {
                    $randomPlayerIndex = rand(0, count($availablePlayers) - 1);
                    $pickedPlayer = $availablePlayers[$randomPlayerIndex];

                    // Avoid duplicate players in the team
                    if (!in_array($pickedPlayer, array_merge(...array_values($selectedPlayers)))) {
                        $selectedPlayers[$selectedPosition][] = $pickedPlayer;
                    }
                }
            }

            // Add the generated team to the list
            $generatedTeams[] = $selectedPlayers;
            $generatedTeamsCount++;
        }

        // Return the generated teams as JSON response
        return response()->json($generatedTeams);
    }


}
