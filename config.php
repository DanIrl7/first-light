<?php

return [
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'born_here',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],

    // Wikimedia asks API clients to identify themselves with a descriptive User-Agent.
    'user_agent' => 'BornHere/0.1 (https://github.com/DanIrl7; portfolio project)',

    // How long each kind of public data stays cached, in seconds.
    'cache_ttl' => [
        'wiki' => 30 * 86400,
        'weather' => 365 * 86400,
        'chart' => 365 * 86400,
    ],

    // Admin password is set in idea-box, not here. Born Here has no login.
];
