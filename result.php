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
<html lang="en"<?= $videoId !== null ? ' class="has-gate"' : '' ?>>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Born Here · <?= e($day['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&amp;family=JetBrains+Mono:wght@400;500&amp;family=Playfair+Display:ital,wght@0,400;0,700;0,800;1,400;1,600&amp;display=swap">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/day.css">
    <link rel="stylesheet" href="assets/music.css">
    <script src="assets/dev-motion.js"></script>
    <script>document.documentElement.classList.add('js');</script>
</head>
<body class="page-day">
    <?php if ($videoId !== null): ?>
        <!-- Covers the page while the music video loads. gate.js removes it after the click. -->
        <div class="gate" id="gate" role="dialog" aria-modal="true" aria-labelledby="gate-title">
            <div class="gate-inner">
                <p class="eyebrow">Your day is ready</p>
                <h2 class="gate-title" id="gate-title"><?= e($day['name']) ?>, here's the day you were born.</h2>
                <p class="gate-song">With "<?= e($song['song']) ?>" by <?= e($song['artist']) ?> playing</p>
                <button type="button" class="gate-button is-loading" id="gate-start" disabled>Preparing the music…</button>
                <p class="gate-note" id="gate-note" role="status">Loading the music video.</p>
                <button type="button" class="gate-skip" id="gate-skip" hidden>Continue without sound</button>
            </div>
        </div>

        <button type="button" class="sound-toggle is-muted" id="sound-toggle" aria-pressed="false" aria-label="Turn the music on">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path class="speaker" d="M4 9.5h3.5L12 5.5v13l-4.5-4H4z"/>
                <path class="waves" d="M15.5 9a4.5 4.5 0 0 1 0 6M18 6.5a8 8 0 0 1 0 11"/>
                <path class="slash" d="M16 9.5l5 5M21 9.5l-5 5"/>
            </svg>
            <span class="sound-label"><?= e($song['song']) ?></span>
        </button>
    <?php endif; ?>

    <div class="sky" aria-hidden="true">
        <span class="sky-night"></span>
        <span class="sky-dawn"></span>
        <span class="sky-noon"></span>
        <span class="sky-dusk"></span>
        <i class="sky-orb"></i>
    </div>

    <div class="progress" aria-hidden="true"><span></span></div>

    <div class="clock" aria-hidden="true">
        <span class="clock-time" id="clock-time">00:00</span>
        <span class="clock-date"><?= e($birthday->format('j M Y')) ?></span>
    </div>

    <!-- ScrollSmoother moves #smooth-content. Anything fixed to the screen stays outside it (sky, progress, clock). -->
    <div id="smooth-wrapper">
    <div id="smooth-content">
    <main class="day">
        <section class="scene scene-hero">
            <?php if ($cityImage !== null): ?>
                <div class="hero-media">
                    <img class="hero-img" src="<?= e($cityImage) ?>" alt="<?= e($place['name']) ?>" fetchpriority="high">
                </div>
                <div class="hero-shade"></div>
            <?php endif; ?>

            <div class="hero-copy">
                <p class="eyebrow">Born here</p>
                <h1 data-split><?= e($day['name']) ?>, you arrived in <em><?= e($place['name']) ?></em>.</h1>
                <p class="meta">
                    <?= e($birthday->format('j F Y')) ?> · <?= e($region . $place['country']) ?>
                </p>
            </div>

            <div class="scroll-cue" aria-hidden="true">Scroll</div>
        </section>

        <section class="scene scene-place">
            <div class="place-grid">
                <div>
                    <p class="eyebrow" data-reveal>The place</p>
                    <?php if (!empty($day['city']['extract'])): ?>
                        <p class="lede" data-scrub><?= e($day['city']['extract']) ?></p>
                        <?php if ($cityUrl !== null): ?>
                            <p data-reveal><a href="<?= e($cityUrl) ?>" rel="noopener">Read more on Wikipedia</a></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="lede" data-reveal><?= e($place['name']) ?>, <?= e($place['country']) ?>.</p>
                    <?php endif; ?>
                </div>

                <div class="weather" data-reveal>
                    <p class="eyebrow">The weather</p>
                    <?php if ($day['weather'] !== null): ?>
                        <?php
                        $temperature = (float) $day['weather']['temperature_c'];
                        $decimals = $temperature === round($temperature) ? 0 : 1;
                        ?>
                        <span class="temp" data-speed="1.12" data-count="<?= e((string) $temperature) ?>" data-decimals="<?= $decimals ?>"><?= e(number_format($temperature, $decimals, '.', '')) ?></span>
                        <p class="weather-detail">
                            °C · <?= e($day['weather']['condition']) ?>
                            <span class="meta">at <?= e($day['weather']['time']) ?></span>
                        </p>
                    <?php else: ?>
                        <p class="meta">Weather for this day isn't available.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($day['leaders']['head_of_state'] !== null || $day['leaders']['head_of_government'] !== null): ?>
                <div class="leaders" data-reveal>
                    <p class="eyebrow">In charge of <?= e($place['country']) ?> that day</p>
                    <div class="leaders-row">
                        <?php foreach (['Head of state' => 'head_of_state', 'Head of government' => 'head_of_government'] as $title => $key): ?>
                            <?php $leader = $day['leaders'][$key]; ?>
                            <?php if ($leader !== null): ?>
                                <figure class="leader">
                                    <?php if (safe_url($leader['image']) !== null): ?>
                                        <img src="<?= e(safe_url($leader['image'])) ?>" alt="" width="64" height="64" loading="lazy">
                                    <?php endif; ?>
                                    <figcaption><?= e($title) ?><strong><?= e($leader['name']) ?></strong></figcaption>
                                </figure>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="scene scene-events">
            <div class="ghost" aria-hidden="true" data-speed="0.8"><span><?= e($birthday->format('Y')) ?> · <?= e($birthday->format('Y')) ?> · <?= e($birthday->format('Y')) ?> · <?= e($birthday->format('Y')) ?></span></div>
            <p class="eyebrow">The world that day</p>
            <h2 data-split>What happened that day</h2>
            <p class="note" data-reveal>Events Wikipedia lists for this date in <?= e($birthday->format('Y')) ?>, in the order its monthly article gives them.</p>

            <?php if ($day['events'] === []): ?>
                <p class="meta">No events are listed for this date in <?= e($birthday->format('Y')) ?>.</p>
            <?php endif; ?>

            <?php foreach ($day['events'] as $event): ?>
                <?php $eventImage = safe_url($event['image'] ?? null); ?>
                <article class="event<?= $eventImage === null ? ' no-media' : '' ?>">
                    <?php if ($eventImage !== null): ?>
                        <div class="event-media" data-speed="0.94">
                            <img src="<?= e($eventImage) ?>" alt="" loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="event-body" data-lag="0.25">
                        <p class="event-year"><?= e((string) ($event['year'] ?? 'Year unknown')) ?></p>
                        <p class="event-text"><?= e($event['text'] ?? '') ?></p>
                        <?php if (!empty($event['location']) || !empty($event['time'])): ?>
                            <p class="meta">
                                <?= e(implode(' · ', array_filter([$event['location'] ?? null, $event['time'] ?? null]))) ?>
                            </p>
                        <?php endif; ?>
                        <?php if (safe_url($event['url'] ?? null) !== null): ?>
                            <p><a href="<?= e(safe_url($event['url'])) ?>" rel="noopener">Wikipedia</a></p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="scene scene-people">
            <p class="eyebrow">Your birthday twins</p>
            <h2 data-split>Born on this day</h2>
            <p class="note" data-reveal>Five of the most widely covered people born on this date, ranked the same way. Shown for what they're known for, not judged.</p>

            <?php if ($day['people'] === []): ?>
                <p class="meta" style="margin-inline: var(--gutter)">No people could be loaded for this date right now.</p>
            <?php else: ?>
                <div class="people-track">
                    <?php foreach ($day['people'] as $person): ?>
                        <article class="person">
                            <?php if (safe_url($person['image'] ?? null) !== null): ?>
                                <img class="person-photo" src="<?= e(safe_url($person['image'])) ?>" alt="<?= e($person['name']) ?>">
                            <?php else: ?>
                                <div class="person-photo person-placeholder" aria-hidden="true"><?= e(mb_substr($person['name'], 0, 1)) ?></div>
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
                                <p><a href="<?= e(safe_url($person['url'])) ?>" rel="noopener">Wikipedia</a></p>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="scene scene-music" id="music" data-video-id="<?= e($videoId ?? '') ?>">
            <p class="eyebrow">The soundtrack</p>
            <h2 data-split>The top song that week</h2>
            <?php if ($song !== null): ?>
                <p class="song-title" data-reveal>"<?= e($song['song']) ?>"</p>
                <p class="meta" data-reveal>
                    by <?= e($song['artist']) ?>
                    · No. 1 on the <?= e($song['chart']) ?> chart, week of <?= e((new DateTimeImmutable($song['chart_date']))->format('j F Y')) ?>
                </p>

                <?php if ($videoId !== null): ?>
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

        <section class="scene scene-certificate">
            <div class="certificate">
                <svg class="seal" viewBox="0 0 120 120" aria-hidden="true">
                    <circle cx="60" cy="60" r="54" pathLength="1"/>
                    <circle cx="60" cy="60" r="45" pathLength="1"/>
                    <path d="M38 62 L54 78 L84 44" pathLength="1"/>
                </svg>
                <p class="eyebrow">Certificate of arrival</p>
                <p class="certificate-text" data-scrub>
                    This certifies that <strong><?= e($day['name']) ?></strong> arrived in
                    <strong><?= e($place['name']) ?></strong>, <?= e($place['country']) ?>,
                    on <strong><?= e($birthday->format('j F Y')) ?></strong>.
                </p>
            </div>
        </section>

        <p class="admin-link"><a href="index.php">Start over</a></p>
    </main>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/ScrollSmoother.min.js"></script>
    <script src="assets/images.js"></script>
    <script src="assets/day.js"></script>
    <script src="assets/gate.js"></script>
    <script src="assets/cinema.js"></script>
    <script src="assets/music.js"></script>
</body>
</html>
