<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/day.php';
require __DIR__ . '/includes/music.php';
require __DIR__ . '/includes/youtube.php';

// The first build for a birthday calls several slow APIs, so it can run longer than PHP's default 30 seconds.
set_time_limit(120);

if (empty($_SESSION['profile'])) {
    set_flash('Enter your details first.');
    redirect('index.php');
}

// Only allow links and images from https, so external data can't inject a javascript: URL.
function safe_url(?string $url): ?string
{
    return ($url !== null && str_starts_with($url, 'https://')) ? $url : null;
}

// Wikipedia gives years before 1 AD as negative numbers, so -83 is shown as 83 BC.
function year_label(?int $year): string
{
    if ($year === null) {
        return 'year unknown';
    }

    return $year < 0 ? abs($year) . ' BC' : (string) $year;
}

// Ask Wikimedia for a larger copy of an image. It only generates certain widths, so use a standard size.
function display_image(?string $url, bool $hero = false): ?string
{
    if ($url === null) {
        return null;
    }

    $width = $hero ? 1280 : 960;
    $url = preg_replace('#/\d+px-([^/?]+)#', "/{$width}px-$1", $url);
    $url = preg_replace('/width=\d+/', "width={$width}", $url);

    return safe_url($url);
}

$day = build_day($_SESSION['profile']);

$day['city']['image'] = display_image($day['city']['image'] ?? null, true);

foreach ($day['events'] as $i => $event) {
    $day['events'][$i]['image'] = display_image($event['image'] ?? null);
}

foreach ($day['people'] as $i => $person) {
    $day['people'][$i]['image'] = display_image($person['image'] ?? null);
}
$birthday = new DateTimeImmutable($day['birthday']);
$place = $day['place'];
$region = $place['region'] !== '' ? $place['region'] . ', ' : '';
$cityImage = safe_url($day['city']['image'] ?? null);
$cityUrl = safe_url($day['city']['url'] ?? null);
$song = $day['song'];
$searchUrl = $song !== null
    ? 'https://www.youtube.com/results?search_query=' . rawurlencode($song['song'] . ' ' . $song['artist'])
    : null;
// A manual entry in data/videos.csv wins; otherwise look for an embeddable video through the API.
$videoId = $song !== null
    ? (music_video_id($song['song'], $song['artist']) ?? youtube_embeddable_id($song['song'], $song['artist']))
    : null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Born Here · <?= e($day['name']) ?></title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/day.css">
    <link rel="stylesheet" href="assets/music.css">
    <script>document.documentElement.classList.add('js');</script>
</head>
<body>
    <div class="clock" aria-hidden="true">
        <span class="clock-time" id="clock-time">00:00</span>
        <span class="clock-date"><?= e($birthday->format('j M Y')) ?></span>
    </div>

    <main class="day">
        <section class="panel panel-intro">
            <p class="eyebrow">Born here</p>
            <h1><?= e($day['name']) ?>, you arrived in <?= e($place['name']) ?>.</h1>
            <p class="meta">
                <?= e($birthday->format('j F Y')) ?> · <?= e($region . $place['country']) ?>
            </p>

            <?php if ($cityImage !== null): ?>
                <img class="hero" src="<?= e($cityImage) ?>" alt="<?= e($place['name']) ?>" loading="lazy">
            <?php endif; ?>

            <?php if (!empty($day['city']['extract'])): ?>
                <p><?= e($day['city']['extract']) ?></p>
                <?php if ($cityUrl !== null): ?>
                    <p><a href="<?= e($cityUrl) ?>" rel="noopener">Read more on Wikipedia</a></p>
                <?php endif; ?>
            <?php endif; ?>

            <div class="weather">
                <?php if ($day['weather'] !== null): ?>
                    <strong><?= e((string) $day['weather']['temperature_c']) ?>°C</strong>
                    · <?= e($day['weather']['condition']) ?>
                    <span class="meta">at <?= e($day['weather']['time']) ?></span>
                <?php else: ?>
                    <span class="meta">Weather for this day isn't available.</span>
                <?php endif; ?>
            </div>

            <?php if ($day['leaders']['head_of_state'] !== null || $day['leaders']['head_of_government'] !== null): ?>
                <div class="leaders">
                    <p class="meta">In charge of <?= e($place['country']) ?> that day</p>
                    <?php foreach (['Head of state' => 'head_of_state', 'Head of government' => 'head_of_government'] as $title => $key): ?>
                        <?php $leader = $day['leaders'][$key]; ?>
                        <?php if ($leader !== null): ?>
                            <figure class="leader">
                                <?php if (safe_url($leader['image']) !== null): ?>
                                    <img src="<?= e(safe_url($leader['image'])) ?>" alt="<?= e($leader['name']) ?>" width="96" loading="lazy">
                                <?php endif; ?>
                                <figcaption><?= e($title) ?>: <strong><?= e($leader['name']) ?></strong></figcaption>
                            </figure>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel">
            <h2>What happened that day</h2>
            <p class="note">Events Wikipedia lists for this date in <?= e($birthday->format('Y')) ?>, in the order its monthly article gives them.</p>

            <?php if ($day['events'] === []): ?>
                <p class="meta">No events are listed for this date in <?= e($birthday->format('Y')) ?>.</p>
            <?php endif; ?>

            <?php foreach ($day['events'] as $event): ?>
                <article class="card event">
                    <?php if (safe_url($event['image'] ?? null) !== null): ?>
                        <img src="<?= e(safe_url($event['image'])) ?>" alt="" loading="lazy">
                    <?php endif; ?>
                    <p class="event-year"><?= e((string) ($event['year'] ?? 'Year unknown')) ?></p>
                    <p><?= e($event['text'] ?? '') ?></p>
                    <?php if (!empty($event['location']) || !empty($event['time'])): ?>
                        <p class="meta">
                            <?= e(implode(' · ', array_filter([$event['location'] ?? null, $event['time'] ?? null]))) ?>
                        </p>
                    <?php endif; ?>
                    <?php if (safe_url($event['url'] ?? null) !== null): ?>
                        <a href="<?= e(safe_url($event['url'])) ?>" rel="noopener">Wikipedia</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="panel">
            <h2>Born on this day</h2>
            <p class="note">Five of the most widely covered people born on this date, ranked the same way. Shown for what they're known for, not judged.</p>

            <?php if ($day['people'] === []): ?>
                <p class="meta">No people could be loaded for this date right now.</p>
            <?php endif; ?>

            <?php foreach ($day['people'] as $person): ?>
                <article class="card person">
                    <?php if (safe_url($person['image'] ?? null) !== null): ?>
                        <img src="<?= e(safe_url($person['image'])) ?>" alt="<?= e($person['name']) ?>" loading="lazy">
                    <?php endif; ?>
                    <h3><?= e($person['name']) ?></h3>
                    <p class="meta">
                        Born <?= e(year_label($person['born_year'] ?? null)) ?>
                        in <?= e($person['birthplace'] ?? 'an unknown place') ?>
                    </p>
                    <?php if (!empty($person['known_for'])): ?>
                        <p><?= e(ucfirst($person['known_for'])) ?></p>
                    <?php endif; ?>
                    <?php if (safe_url($person['url'] ?? null) !== null): ?>
                        <a href="<?= e(safe_url($person['url'])) ?>" rel="noopener">Wikipedia</a>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="panel panel-music" id="music" data-video-id="<?= e($videoId ?? '') ?>">
            <h2>The top song that week</h2>
            <?php if ($song !== null): ?>
                <p class="song-title">"<?= e($song['song']) ?>"</p>
                <p class="meta">
                    by <?= e($song['artist']) ?>
                    · No. 1 on the <?= e($song['chart']) ?> chart, week of <?= e((new DateTimeImmutable($song['chart_date']))->format('j F Y')) ?>
                </p>

                <?php if ($videoId !== null): ?>
                    <button type="button" id="music-play" class="music-button">Turn on sound</button>
                    <div class="video">
                        <div id="music-player"></div>
                    </div>
                    <p class="note" id="music-status"></p>
                <?php else: ?>
                    <p class="note">
                        There's no music video linked for this song yet. You can
                        <a href="<?= e($searchUrl) ?>" rel="noopener">search for it on YouTube</a>.
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <p class="meta">The Billboard Hot 100 chart starts in 1958, so there's no chart song for this date.</p>
            <?php endif; ?>
        </section>

        <section class="panel panel-certificate">
            <h2>Certificate of arrival</h2>
            <p class="certificate-text">
                This certifies that <strong><?= e($day['name']) ?></strong> arrived in
                <strong><?= e($place['name']) ?></strong>, <?= e($place['country']) ?>,
                on <strong><?= e($birthday->format('j F Y')) ?></strong>.
            </p>
            <button type="button" class="print-button" onclick="window.print()">Print certificate</button>
        </section>

        <p class="admin-link"><a href="index.php">Start over</a></p>
    </main>

    <script src="assets/day.js"></script>
    <script src="assets/music.js"></script>
</body>
</html>
