<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams</title>
    <!-- Latest Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom Styles for card shadow and hover effect */
        .player-card {
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease;
        }

        .player-card:hover {
            transform: translateY(-8px);
            box-shadow: 0px 12px 18px rgba(0, 0, 0, 0.15);
        }

        .role-card {
            transition: transform 0.3s ease, background-color 0.3s ease;
        }

        .role-card:hover {
            transform: scale(1.05);
            background-color: #f9fafb;
        }

        .role-card .role-header {
            font-size: 1.125rem;
            color: #3b82f6;
        }

        /* Additional styles for ground-style UI */
        .team {
            background-color: #34d399;
            /* Green background */
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        .team h2 {
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .role-header {
            font-size: 1rem;
            /* Slightly smaller font */
            color: #ffffff;
        }

        .player-card p {
            font-size: 12px !important;
            /* Smaller text */
        }

        /* Making the player cards smaller and more compact */
        .player-card {
            padding: 1rem;
            background-color: white;
            border-radius: 0.5rem;
        }

        /* Responsiveness */
        @media (max-width: 768px) {
            .team {
                padding: 1.5rem;
                /* Reduce padding on smaller screens */
            }

            .role-header {
                font-size: 12px !important;
                /* Smaller role header text on mobile */
            }

            .player-card p {
                font-size: 12px !important;
                /* Even smaller text for players on mobile */
            }
        }
    </style>
</head>

<body class="bg-gray-100 text-gray-800 font-sans">
<div class="container mx-auto p-8 min-h-screen flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-4xl font-semibold mb-8">Teams</h1>

        <!-- Teams Section -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($teams as $key => $team)
                <div class="team">
                    <h2 class="text-center">Team {{ $key + 1 }}</h2>
                    @foreach($team as $key1 => $role)
                        <div class="role mb-6">
                            <h3 class="role-header text-center mb-4">{{ $key1 }}</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($role as $player)
                                    <div class="player-card shadow-md" style="display: flex;flex-direction: column;align-items: center;">
                                        <p class="text-[10px] font-medium">{{ $player->name }}</p>
                                        <p class="text-[10px] text-gray-600">
                                            @if($player->is_captain=="yes")
                                                <span class="text-green-500 text-[10px] font-bold">Captain</span>
                                            @endif
                                            @if($player->is_vice_captain=="yes")
                                                <span class="text-blue-500 text-[10px] font-bold">Vice Captain</span>
                                            @endif
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>

</body>

</html>