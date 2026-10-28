<?php

// Local development uses the defaults. A hosted deployment sets these as environment variables:
// DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_SSL, and YOUTUBE_API_KEY.
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'name' => getenv('DB_NAME') ?: 'born_here',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
        // Hosted MySQL services such as TiDB Cloud require encrypted connections.
        'ssl' => in_array(strtolower((string) getenv('DB_SSL')), ['1', 'true', 'yes'], true),
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
