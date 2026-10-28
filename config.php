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

    // YouTube Data API key. Set the YOUTUBE_API_KEY environment variable, never commit the key.
    // While it's empty, songs without a video in data/videos.csv keep the search link.
    'youtube_api_key' => getenv('YOUTUBE_API_KEY') ?: '',

    // How long each kind of public data stays cached, in seconds.
    'cache_ttl' => [
        'wiki' => 30 * 86400,
        'weather' => 365 * 86400,
        'chart' => 365 * 86400,
        'youtube' => 30 * 86400,
    ],
];
