<?php
// Finds a YouTube video for every Billboard Hot 100 number one (1958 onward) and adds the embeddable
// ones to data/videos.csv. Run from the project folder, with your own key in YOUTUBE_API_KEY:
//   php scripts/build_videos.php [--limit=90]
//
// Each new search costs 100 units of the YouTube daily quota (10,000 units by default), so --limit caps
// the searches per run. Songs already in the CSV or the cache are skipped, so re-running picks up where
// the last run stopped.
declare(strict_types=1);

require __DIR__ . '/../includes/app.php';
require __DIR__ . '/../includes/billboard.php';
require __DIR__ . '/../includes/music.php';
require __DIR__ . '/../includes/youtube.php';

if (PHP_SAPI !== 'cli') {
    exit("Run this script from the command line.\n");
}

$limit = 90;

foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--limit=(\d+)$/', $arg, $match)) {
        $limit = (int) $match[1];
    }
}

// Collect every distinct number one, keyed so each song is only looked up once.
$songs = [];

for ($year = 1958; $year <= (int) date('Y'); $year++) {
    foreach (billboard_year_rows($year) as $row) {
        $artist = clean_chart_text($row['artist']);
        $song = clean_chart_text($row['song']);
        $songs[music_key($artist, $song)] = [$artist, $song];
    }

    echo "Read $year\n";
}

echo 'Distinct number ones: ' . count($songs) . "\n";

if (app_config()['youtube_api_key'] === '') {
    exit("Set YOUTUBE_API_KEY before running this script.\n");
}

$cached = db()->prepare('SELECT 1 FROM cache_entries WHERE cache_key = ? AND expires_at > NOW()');
$csv = __DIR__ . '/../data/videos.csv';
$handle = fopen($csv, 'a');

if ($handle === false) {
    exit("Could not open data/videos.csv for writing.\n");
}

$searches = 0;
$added = 0;
$skipped = 0;
$deferred = 0;

foreach ($songs as [$artist, $song]) {
    // Already listed by hand or from an earlier run.
    if (music_video_id($song, $artist) !== null) {
        $skipped++;
        continue;
    }

    // Only a search that isn't cached uses quota, so the limit counts those alone.
    $cached->execute(['youtube:' . md5(music_key($artist, $song))]);

    if ($cached->fetchColumn() === false) {
        if ($searches >= $limit) {
            $deferred++;
            continue;
        }

        $searches++;
    }

    $videoId = youtube_embeddable_id($song, $artist);

    if ($videoId !== null) {
        fputcsv($handle, [$artist, $song, $videoId], ',', '"', '');
        $added++;
        echo "Added: $artist - $song\n";
    }
}

fclose($handle);

echo "\nSearches this run: $searches (limit $limit)\n";
echo "Added this run: $added\n";
echo "Already listed: $skipped\n";
echo "No embeddable video found this run: " . ($searches - $added) . "\n";
echo "Not searched yet (over the limit): $deferred\n";
echo "Run the script again to continue with the songs not yet searched.\n";
