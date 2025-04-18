<?php

namespace App\Http\Controllers\GenModel;

use App\Http\Controllers\Controller;
use App\Models\DreamTeams;
use App\Models\Ground;
use App\Models\Matches;
use App\Models\Player;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PredictFlow extends Controller
{
    public function matchDetails($match_no = "match_34")
    {
        if (!$match_no) {
            return response()->json(['error' => 'Match number is required.'], 400);
        }

        $matchDetails = Matches::where('match_no', $match_no)->first();
        if (!$matchDetails) {
            return response()->json(['error' => 'Match not found.'], 404);
        }

        if (!$matchDetails->team_1 || !$matchDetails->team_2) {
            return response()->json(['error' => 'Both Team 1 and Team 2 must be assigned.'], 404);
        }

        $roles = ['Wicket Keeper', 'Batter', 'All Rounder', 'Bowler'];
        $roleMap = [
            'Wicket Keeper' => 'wicketKeepers',
            'Batter' => 'batters',
            'All Rounder' => 'allrounders',
            'Bowler' => 'bowlers',
        ];

        $team1 = $team2 = [];

        foreach ($roles as $role) {
            $players1 = Player::where('role', $role)->where('rate','<','3')->where('team_id', $matchDetails->team_1)->get();
            $players2 = Player::where('role', $role)->where('rate','<','3')->where('team_id', $matchDetails->team_2)->get();

            if ($players1->isEmpty() || $players2->isEmpty()) {
                return response()->json(['error' => "Missing players for role: $role"], 404);
            }

            $key = $roleMap[$role];
            $team1[$key] = $players1;
            $team2[$key] = $players2;
        }

        $groundData = null;
        if ($matchDetails->ground_id) {
            $groundData = Ground::where('ground_id', $matchDetails->ground_id)->first();
            if (!$groundData) {
                return response()->json(['error' => 'Ground not found.'], 404);
            }
        } else {
            return response()->json(['error' => 'Ground not assigned.'], 404);
        }

        return $this->generateTeam([
            'match' => $matchDetails,
            'team_1' => $team1,
            'team_2' => $team2,
            'ground' => $groundData,
        ]);
    }

    public function generateTeam($details = [])
    {
        $allPlayers = collect();

        // Merge all players from both teams (Team 1 and Team 2) for each role.
        foreach (['wicketKeepers', 'batters', 'allrounders', 'bowlers'] as $role) {
            $players1 = $details['team_1'][$role];
            $players2 = $details['team_2'][$role];

            $merged = $players1->merge($players2)->map(function ($player) use ($role) {
                $player['role_key'] = $role;
                return $player;
            });

            $allPlayers = $allPlayers->merge($merged);
        }

        // First, prioritize players with rate 1 or 2 by assigning them a higher priority for sorting.
        $allPlayers = $allPlayers->sortBy(function ($player) {
            return $player['rate'] == 1 || $player['rate'] == 2 ? 0 : 1;
        })->sortBy('rate')->values(); // After prioritizing rate 1 and 2, sort by the actual rate value.

        $expectedTeams = config("constant.expectedTeams.PredictFlow", 1);
        $maxCreditScore = config("constant.maxCreditScore", 100);
        $playersPerTeam = config("constant.playersPerTeam", 11);
        $roleLimits = config("constant.players_limit", []);

        Log::info('Generating teams with config:', [
            'expectedTeams' => $expectedTeams,
            'maxCreditScore' => $maxCreditScore,
            'playersPerTeam' => $playersPerTeam,
            'roleLimits' => $roleLimits,
        ]);

        $generatedTeams = [];
        $generatedTeamsCount = 0;
        $maxAttempts = 1000000;
        $attempts = 0;

        // Split captains and vice-captains based on their 'is_captain' and 'is_vice_captain' flags, also filter by rate 1 and 2
        $captainCandidates = $allPlayers->where('is_captain', 'yes')->filter(function ($player) {
            return $player['rate'] == 1 || $player['rate'] == 2;
        })->values();

        $viceCaptainCandidates = $allPlayers->where('is_vice_captain', 'yes')->filter(function ($player) {
            return $player['rate'] == 1 || $player['rate'] == 2;
        })->values();

        // If we can't find any valid candidates with rate 1 or 2, fallback to other rates.
        if ($captainCandidates->isEmpty()) {
            $captainCandidates = $allPlayers->where('is_captain', 'yes')->values();
        }

        if ($viceCaptainCandidates->isEmpty()) {
            $viceCaptainCandidates = $allPlayers->where('is_vice_captain', 'yes')->values();
        }

        if ($captainCandidates->isEmpty() || $viceCaptainCandidates->isEmpty()) {
            Log::warning("No captain or vice-captain candidates found.");
            return response()->json(['error' => 'Captain or vice-captain candidates missing.'], 400);
        }

        while ($generatedTeamsCount < $expectedTeams && $attempts < $maxAttempts) {
            $selectedPlayers = collect();
            $roleCounts = ['wicketKeepers' => 0, 'batters' => 0, 'allrounders' => 0, 'bowlers' => 0];
            $selectedCredit = 0;

            // Shuffle players after sorting by their preference rate.
            $shuffledPlayers = $allPlayers->shuffle();

            foreach ($shuffledPlayers as $player) {
                $role = $player['role_key'];
                $maxLimit = $roleLimits[$role]['max'] ?? PHP_INT_MAX;

                // If we haven't yet selected enough players of this role, and credit allows, add them to the team.
                if ($roleCounts[$role] < $maxLimit && !$selectedPlayers->contains('id', $player['id'])) {
                    if ($selectedCredit + (int) $player['credit_score'] <= $maxCreditScore) {
                        $selectedPlayers->push($player);
                        $roleCounts[$role]++;
                        $selectedCredit += (int) $player['credit_score'];
                    }
                }

                // Stop if we have selected enough players for the team and role limits are satisfied.
                if (
                    $selectedPlayers->count() === $playersPerTeam &&
                    $this->roleLimitsSatisfied($roleCounts, $roleLimits)
                ) {
                    break;
                }
            }

            // If we don't have enough players or role limits are not satisfied, continue to the next attempt.
            if ($selectedPlayers->count() !== $playersPerTeam || !$this->roleLimitsSatisfied($roleCounts, $roleLimits)) {
                $attempts++;
                continue;
            }

            $selectedIds = $selectedPlayers->pluck('id')->toArray();

            // Select captains and vice-captains from the selected players
            $eligibleCaptains = $captainCandidates->whereIn('id', $selectedIds)->values();
            $eligibleViceCaptains = $viceCaptainCandidates->whereIn('id', $selectedIds)->values();

            if ($eligibleCaptains->isEmpty() || $eligibleViceCaptains->isEmpty()) {
                $attempts++;
                continue;
            }

            // Preferably select a captain with rate 1 or 2
            $captain = $eligibleCaptains->random();
            // Select a vice-captain from the remaining pool, ensuring it's not the same as the captain
            $viceCaptainOptions = $eligibleViceCaptains->where('id', '!=', $captain['id']);

            if ($viceCaptainOptions->isEmpty()) {
                $attempts++;
                continue;
            }

            $viceCaptain = $viceCaptainOptions->random();

            $playersString = implode(',', $selectedIds);

            $exists = DreamTeams::where('players', $playersString)
                ->where('captain_id', $captain['id'])
                ->where('vice_captain_id', $viceCaptain['id'])
                ->exists();

            if (!$exists) {
                DreamTeams::create([
                    'match_no' => $details['match']->match_no,
                    'dream_id' => Str::uuid(),
                    'players' => $playersString,
                    'captain_id' => $captain['id'],
                    'vice_captain_id' => $viceCaptain['id'],
                    'total_credit' => $selectedCredit,
                ]);

                $generatedTeams[] = [
                    'players' => $selectedPlayers,
                    'captain_id' => $captain['id'],
                    'vice_captain_id' => $viceCaptain['id'],
                ];

                $generatedTeamsCount++;
            }

            $attempts++;
        }

        if (empty($generatedTeams)) {
            Log::warning("No teams generated after $maxAttempts attempts.");
            return response()->json(['error' => 'No valid teams generated. Check players and constraints.'], 400);
        }

        return response()->json($generatedTeams);
    }

    private function roleLimitsSatisfied($counts, $roleLimits)
    {
        foreach ($roleLimits as $role => $limits) {
            if (!isset($counts[$role]) || $counts[$role] < $limits['min']) {
                return false;
            }
        }
        return true;
    }
}
