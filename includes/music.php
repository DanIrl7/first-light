<?php
declare(strict_types=1);

// YouTube video ID for a chart song, read from data/videos.csv (columns: artist, song, youtube_id).
// Returns null when the song isn't listed, so the page can fall back to a search link.
function music_video_id(string $song, string $artist): ?string
{
    static $map = null;

    if ($map === null) {
        $map = [];
        $file = __DIR__ . '/../data/videos.csv';

        if (is_readable($file) && ($handle = fopen($file, 'r')) !== false) {
            fgetcsv($handle); // skip the header row

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 3) {
                    continue;
                }

                [$rowArtist, $rowSong, $videoId] = array_map('trim', $row);

                // YouTube video IDs are always 11 characters: letters, digits, underscore, hyphen.
                if (!preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {
                    continue;
                }

                $map[music_key($rowArtist, $rowSong)] = $videoId;
            }

            fclose($handle);
        }
    }

    return $map[music_key($artist, $song)] ?? null;
}

// Matching ignores case and surrounding spaces, so "sinéad o'connor" finds "Sinéad O'Connor".
function music_key(string $artist, string $song): string
{
    return mb_strtolower(trim($artist)) . '|' . mb_strtolower(trim($song));
}
