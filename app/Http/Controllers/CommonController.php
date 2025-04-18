<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\TeamStats;
use Illuminate\Http\Request;
use App\Models\Matches;
use App\Models\DreamTeams;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CommonController extends Controller
{
    public function updateStats(Request $request)
    {
        // // Validate the request
        // $validated = $request->validate([
        //     'match_no' => 'required|string',
        //     'stats' => 'required|array',
        //     'stats.*' => 'required|numeric'
        // ]);

        $match_no = $request->match_no;
        $stats = $request->stats;

        // Check if the match exists
        $isExist = Matches::where('match_no', $match_no)->exists();
        if (!$isExist) {
            return response()->json(['error' => 'Match number is invalid.'], 400);
        }

        // Get the first 10 dream players (sorted by player ID)
        $dreamPlayers = array_slice(array_keys($stats), 0, 10);

        // Get Dream Teams for this match
        $dreamTeams = DreamTeams::where('match_no', $match_no)->get();

        foreach ($dreamTeams as $dreamTeam) {
            $dream_team_id = $dreamTeam->id;

            // Build the raw SQL query
            $total_dream_players = DB::select("
                SELECT
                    COUNT(*) AS matched_count,
                    GROUP_CONCAT(player_id ORDER BY player_id) AS matched_ids
                FROM (
                    SELECT
                        dt.players,
                        dt.id,
                        id.player_id
                    FROM
                        dream_teams dt
                    CROSS JOIN (
                        SELECT ? AS player_id UNION ALL
                        SELECT ? UNION ALL
                        SELECT ? UNION ALL
                        SELECT ? UNION ALL
                        SELECT ? UNION ALL
                        SELECT ? UNION ALL
                        SELECT ? UNION ALL
                        SELECT ? UNION ALL
                        SELECT ? UNION ALL
                        SELECT ?
                    ) AS id
                    WHERE FIND_IN_SET(id.player_id, dt.players) > 0
                    AND dt.id = ?
                ) AS matched_players
                GROUP BY players
                ORDER BY matched_count DESC;
            ", array_merge($dreamPlayers, [$dream_team_id]))[0]->matched_count;

            // Process the player's stats
            $playersCurrent = explode(',', $dreamTeam->players);
            $totalDreamPoints = 0;
            $playersStats = [];

            foreach ($playersCurrent as $playerId) {
                $points = $stats[$playerId] ?? 0;
                if ($playerId == $dreamTeam->captain_id) {
                    $points = ($points * 2);
                }
                if ($playerId == $dreamTeam->vice_captain_id) {
                    $points = ($points * 1.5);
                }
                $playersStats[] = ['player' => $playerId, 'points' => $points];
                $totalDreamPoints += $points;
            }

            // Sort players by points (descending order)
            usort($playersStats, function ($a, $b) {
                return $b['points'] <=> $a['points'];
            });
            $playersStats = json_encode($playersStats);
            // Update or create the TeamStats record
            TeamStats::updateOrCreate(
                ['match_no' => $match_no, 'dream_team_id' => $dream_team_id],
                [
                    'total_dream_players' => $total_dream_players,
                    'dream_points' => $totalDreamPoints,
                    'players_stats' => $playersStats
                ]
            );
        }

        return response()->json([
            'message' => 'Dream teams updated successfully.',
        ], 200);
    }

    public function listTeams(Request $request)
    {
        $match_no = $request->get('match_no');
        $allTeams = DreamTeams::where('match_no', $match_no)->get();
        $teams = [];

        // Define roles you expect
        $expectedRoles = [
            'wicket keeper' => 'Wicket Keeper',
            'batter' => 'Batter',
            'all rounder' => 'All Rounder',
            'bowler' => 'Bowler'
        ];

        foreach ($allTeams as $team) {
            $players = $team->players;
            $captain_id = $team->captain_id;
            $vice_captain_id = $team->vice_captain_id;
            $players = Player::select('id', 'name', 'role')->whereIn('id', explode(',', $players))->get();

            // Initialize grouped roles
            $groupedByRole = [
                'Wicket Keeper' => [],
                'Batter' => [],
                'All Rounder' => [],
                'Bowler' => [],
            ];

            foreach ($players as $player) {
                // Set captain and vice-captain flags
                $player->is_captain = ($player->id == $captain_id) ? "yes" : "no";
                $player->is_vice_captain = ($player->id == $vice_captain_id) ? "yes" : "no";

                // Normalize role to lowercase to handle case insensitivity
                $role = strtolower($player->role);

                // Group players by their normalized roles
                if (array_key_exists($role, $expectedRoles)) {
                    $groupedByRole[$expectedRoles[$role]][] = $player;
                }
            }

            // Store the grouped players in the teams array
            $teams[] = $groupedByRole;
        }
        return view('team', compact('teams'));
    }

}
