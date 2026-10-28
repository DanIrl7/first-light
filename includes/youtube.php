<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/music.php';

// Finds an embeddable YouTube video for a chart song using the YouTube Data API.
// Returns the video ID, or null if there's no API key, no suitable video, or the lookup failed.
// Results are cached, including "no match", so repeat visits don't use API quota.
function youtube_embeddable_id(string $song, string $artist): ?string
{
    $key = app_config()['youtube_api_key'];

    if ($key === '') {
        return null;
    }

    $result = cache_remember(
        'youtube:' . md5(music_key($artist, $song)),
        app_config()['cache_ttl']['youtube'],
        fn () => youtube_search($song, $artist, $key)
    );

    return is_string($result) && $result !== 'none' ? $result : null;
}

// Returns a video ID, 'none' when the API found nothing usable, or null on a failed request.
// Null isn't cached, so a failed request is retried on the next visit.
function youtube_search(string $song, string $artist, string $key): ?string
{
    // Search costs 100 units of the daily quota. Filter to embeddable music videos.
    $search = http_get_json('https://www.googleapis.com/youtube/v3/search?' . http_build_query([
        'part' => 'snippet',
        'q' => "$artist $song official",
        'type' => 'video',
        'videoEmbeddable' => 'true',
        'videoSyndicated' => 'true',
        'videoCategoryId' => '10', // Music
        'maxResults' => 5,
        'key' => $key,
    ]));

    if ($search === null) {
        return null;
    }

    // Keep candidates whose title contains the song name, in the order the API ranked them.
    $candidates = [];

    foreach ($search['items'] ?? [] as $item) {
        $videoId = $item['id']['videoId'] ?? null;
        $title = mb_strtolower($item['snippet']['title'] ?? '');

        if ($videoId !== null && str_contains($title, mb_strtolower($song))) {
            $candidates[] = $videoId;
        }
    }

    if ($candidates === []) {
        return 'none';
    }

    // Confirm each candidate is public and embeddable. The search filter alone isn't always enough.
    $details = http_get_json('https://www.googleapis.com/youtube/v3/videos?' . http_build_query([
        'part' => 'status',
        'id' => implode(',', $candidates),
        'key' => $key,
    ]));

    if ($details === null) {
        return null;
    }

    $statuses = [];

    foreach ($details['items'] ?? [] as $video) {
        $statuses[$video['id']] = $video['status'] ?? [];
    }

    foreach ($candidates as $videoId) {
        $status = $statuses[$videoId] ?? [];

        if (($status['embeddable'] ?? false) === true && ($status['privacyStatus'] ?? '') === 'public') {
            return $videoId;
        }
    }

    return 'none';
}
