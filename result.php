<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/day.php';

if (empty($_SESSION['profile'])) {
    set_flash('Enter your details first.');
    redirect('index.php');
}

// Only allow links and images from https, so external data can't inject a javascript: URL.
function safe_url(?string $url): ?string
{
    return ($url !== null && str_starts_with($url, 'https://')) ? $url : null;
}

$day = build_day($_SESSION['profile']);
$birthday = new DateTimeImmutable($day['birthday']);
$place = $day['place'];
$region = $place['region'] !== '' ? $place['region'] . ', ' : '';
$cityImage = safe_url($day['city']['image'] ?? null);
$cityUrl = safe_url($day['city']['url'] ?? null);
$song = $day['song'];
$searchUrl = $song !== null
    ? 'https://www.youtube.com/results?search_query=' . rawurlencode($song['song'] . ' ' . $song['artist'])
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
        </section>

        <section class="panel">
            <h2>What happened that day</h2>
            <p class="note">The 12 most notable events for this date, chosen by Wikipedia's editors and ranked by how many language editions cover them. Shown oldest first. Times appear only when a source gives one.</p>

            <?php if ($day['events'] === []): ?>
                <p class="meta">No events could be loaded for this date right now.</p>
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
                        Born <?= e((string) ($person['born_year'] ?? 'year unknown')) ?>
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

        <section class="panel panel-music" id="music">
            <h2>The top song that week</h2>
            <?php if ($song !== null): ?>
                <p class="song-title">"<?= e($song['song']) ?>"</p>
                <p class="meta">
                    by <?= e($song['artist']) ?>
                    · No. 1 on the <?= e($song['chart']) ?> chart, week of <?= e((new DateTimeImmutable($song['chart_date']))->format('j F Y')) ?>
                </p>
                <p class="note">
                    The music player isn't connected yet. For now you can
                    <a href="<?= e($searchUrl) ?>" rel="noopener">search for this song on YouTube</a>.
                </p>
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
</body>
</html>
