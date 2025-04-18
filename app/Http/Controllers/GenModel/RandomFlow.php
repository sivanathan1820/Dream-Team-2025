<?php

namespace App\Http\Controllers\GenModel;

use App\Http\Controllers\Controller;
use App\Models\DreamTeams;
use App\Models\Ground;
use App\Models\Matches;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Str;  // For UUID generation

class RandomFlow extends Controller
{
    public function matchDetails($match_no = "match_28")
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
            $batters = Player::where('role', 'Batter')->where('team_id', $matchDetails->team_1)->get();
            $allrounders = Player::where('role', 'All Rounder')->where('team_id', $matchDetails->team_1)->get();
            $bowlers = Player::where('role', 'Bowler')->where('team_id', $matchDetails->team_1)->get();

            if ($wicketKeepers->isEmpty() || $batters->isEmpty() || $allrounders->isEmpty() || $bowlers->isEmpty()) {
                return response()->json(['error' => 'Missing players for Team 1.'], 404);
            }

            // For Team 2
            $team2WicketKeepers = Player::where('role', 'Wicket Keeper')->where('team_id', $matchDetails->team_2)->get();
            $team2Batters = Player::where('role', 'Batter')->where('team_id', $matchDetails->team_2)->get();
            $team2Allrounders = Player::where('role', 'All Rounder')->where('team_id', $matchDetails->team_2)->get();
            $team2Bowlers = Player::where('role', 'Bowler')->where('team_id', $matchDetails->team_2)->get();

            if ($team2WicketKeepers->isEmpty() || $team2Batters->isEmpty() || $team2Allrounders->isEmpty() || $team2Bowlers->isEmpty()) {
                return response()->json(['error' => 'Missing players for Team 2.'], 404);
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
            $selectedId = [];
            $selectedCredit = 0;
            $captain_id = 0;
            $vice_captain_id = 0;

            // Select players for the team
            while (array_sum(array_map('count', $selectedPlayers)) < $playersPerTeam) {
                $randomIndex = random_int(0, count($positionDetails) - 1);
                $positionKeys = array_keys($positionDetails);
                $selectedPosition = $positionKeys[$randomIndex];
                $availablePlayers = $positionDetails[$selectedPosition];

                // Limit checking for each position
                $limit = ${$selectedPosition . "_limit"};

                if (count($selectedPlayers[$selectedPosition]) < $limit) {
                    $randomPlayerIndex = random_int(0, count($availablePlayers) - 1);
                    $pickedPlayer = $availablePlayers[$randomPlayerIndex];

                    // Avoid duplicate players in the team
                    if (!in_array($pickedPlayer, array_merge(...array_values($selectedPlayers))) && ($selectedCredit + (int) $pickedPlayer['credit_score']) <= $maxCreditScore) {
                        $selectedPlayers[$selectedPosition][] = $pickedPlayer;
                        $selectedCredit += (int) $pickedPlayer['credit_score'];
                        $selectedId[] = $pickedPlayer['id'];
                    }
                }
            }

            $captain_id = $selectedId[array_rand($selectedId)];
            $refId = $selectedId;
            unset($refId[array_search($captain_id, $refId)]);
            $vice_captain_id = $refId[array_rand($refId)];

            $isExist = DreamTeams::whereIn('players', $selectedId)
                ->where('captain_id', $captain_id)
                ->where('vice_captain_id', $vice_captain_id)
                ->count();
            $selectedId = implode(',', $selectedId);
            if ($isExist == 0) {
                DreamTeams::create([
                    'match_no' => $details['match']->match_no,
                    'dream_id' => Str::uuid(),
                    'players' => $selectedId,
                    'captain_id' => $captain_id,
                    'vice_captain_id' => $vice_captain_id,
                    'total_credit' => $selectedCredit,
                ]);
                $generatedTeams[] = $selectedPlayers;
                $generatedTeamsCount++;
            }
        }

        return response()->json($generatedTeams);
    }
}
