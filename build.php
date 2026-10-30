<?php
// Builds the day page's data ahead of time and reports each step as it finishes, one JSON object per line,
// so the loading screen can show real progress. Everything it fetches is cached, which makes result.php fast.
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/day.php';
require __DIR__ . '/includes/music.php';
require __DIR__ . '/includes/youtube.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

verify_csrf();

if (empty($_SESSION['profile'])) {
    http_response_code(400);
    exit;
}

// Read what's needed, then release the session lock so other requests from this visitor aren't held up.
$profile = $_SESSION['profile'];
session_write_close();

set_time_limit(120);
ignore_user_abort(false);

// Stream each line as soon as it is ready: no compression and no output buffering in the way.
@ini_set('zlib.output_compression', '0');
@ini_set('output_buffering', '0');

if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}

while (ob_get_level() > 0) {
    ob_end_flush();
}

header('Content-Type: application/x-ndjson; charset=utf-8');
header('Cache-Control: no-store');
header('X-Accel-Buffering: no');

// Some servers hold back the first few kilobytes, so start with padding that the page ignores.
echo str_repeat(' ', 2048) . "\n";
flush();

function emit(array $message): void
{
    echo json_encode($message) . "\n";
    flush();
}

try {
    $day = build_day($profile, fn (string $step, string $state) => emit(['step' => $step, 'state' => $state]));

    // The video lookup can call the YouTube API, so it's a step of its own.
    emit(['step' => 'video', 'state' => 'start']);

    if ($day['song'] !== null) {
        music_video_id($day['song']['song'], $day['song']['artist'])
            ?? youtube_embeddable_id($day['song']['song'], $day['song']['artist']);
    }

    emit(['step' => 'video', 'state' => 'done']);
    emit(['done' => true]);
} catch (Throwable $error) {
    error_log('build.php: ' . $error->getMessage());
    emit(['error' => 'The machine stalled before it reached your day.']);
}
