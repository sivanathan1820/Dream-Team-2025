<?php

return [
    'expectedTeams' => [
        "RandomFlow" => 20,
        "PredictFlow" => 20
    ],
    'playersPerTeam' => 11,
    'maxCreditScore' => 100,
    'players_limit' => [
        'wicketKeepers' => [
            'min' => 1,
            'max' => 8
        ],
        'batters' => [
            'min' => 1,
            'max' => 8
        ],
        'allrounders' => [
            'min' => 1,
            'max' => 8
        ],
        'bowlers' => [
            'min' => 1,
            'max' => 8
        ],
    ]
];