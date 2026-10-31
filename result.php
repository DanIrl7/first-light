<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/day.php';
require __DIR__ . '/includes/music.php';
require __DIR__ . '/includes/youtube.php';

// The first build for a birthday calls several slow APIs, so it can run longer than PHP's default 30 seconds.
set_time_limit(120);

if (empty($_SESSION['profile'])) {
    set_flash('The machine needs coordinates first. Fill in the form to begin.');
    redirect('/');
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

// Sunrise and sunset as hours of the day (6.5 means 06:30), so the sky can turn at the real times.
function clock_hours(?string $time): ?float
{
    return $time === null ? null : (int) substr($time, 0, 2) + (int) substr($time, 3, 2) / 60;
}

$weather = $day['weather'];
$sunrise = clock_hours($weather['sunrise'] ?? null);
$sunset = clock_hours($weather['sunset'] ?? null);
$numbers = $day['numbers'];
$tenThousandth = new DateTimeImmutable($numbers['ten_thousandth']['date']);
$tenThousandthAway = $numbers['ten_thousandth']['days_away'];
?>
<!doctype html>
<html lang="en"<?= $videoId !== null ? ' class="has-gate"' : '' ?>>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>First Light · <?= e($day['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&amp;family=Fraunces:ital,wght@0,400;0,700;0,800;1,400;1,600&amp;family=Manrope:wght@300;400;500;600&amp;display=swap">
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
                <p class="eyebrow">Landing sequence complete</p>
                <h2 class="gate-title" id="gate-title"><?= e($day['name']) ?>, you've landed.</h2>
                <p class="gate-song">Your arrival song: "<?= e($song['song']) ?>" by <?= e($song['artist']) ?></p>
                <button type="button" class="gate-button is-loading" id="gate-start" disabled>Warming up the soundtrack…</button>
                <p class="gate-note" id="gate-note" role="status">Tuning the radio.</p>
                <button type="button" class="gate-skip" id="gate-skip" hidden>Step out without sound</button>
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

    <div class="sky" aria-hidden="true"<?= $sunrise !== null && $sunset !== null && $sunrise < $sunset ? ' data-sunrise="' . e((string) round($sunrise, 3)) . '" data-sunset="' . e((string) round($sunset, 3)) . '"' : '' ?>>
        <span class="sky-night"></span>
        <span class="sky-dawn"></span>
        <span class="sky-noon"></span>
        <span class="sky-dusk"></span>
        <i class="sky-orb"></i>
    </div>

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
                <p class="eyebrow">Where it began</p>
                <h1 data-split><?= e($day['name']) ?>, you arrived in <em><?= e($place['name']) ?></em>.</h1>
                <p class="meta">
                    <?= e($birthday->format('j F Y')) ?> · <?= e($region . $place['country']) ?>
                </p>
            </div>

            <div class="scroll-cue" aria-hidden="true">Scroll to travel</div>
        </section>

        <section class="scene scene-place">
            <div class="place-grid">
                <div>
                    <p class="eyebrow" data-reveal>The destination</p>
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
                        <?php
                        $w = $day['weather'];
                        $facts = array_filter([
                            'High' => isset($w['high_c']) ? number_format((float) $w['high_c'], 1) . ' °C' : null,
                            'Low' => isset($w['low_c']) ? number_format((float) $w['low_c'], 1) . ' °C' : null,
                            'Rain' => isset($w['rain_mm']) ? ($w['rain_mm'] > 0 ? number_format((float) $w['rain_mm'], 1) . ' mm' : 'None') : null,
                            'Wind' => isset($w['wind_kmh']) ? 'Up to ' . number_format((float) $w['wind_kmh'], 0) . ' km/h' : null,
                            'Sunrise' => $w['sunrise'] ?? null,
                            'Sunset' => $w['sunset'] ?? null,
                        ], fn ($value) => $value !== null);
                        ?>
                        <?php if ($facts !== []): ?>
                            <dl class="weather-facts">
                                <?php foreach ($facts as $label => $value): ?>
                                    <div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
                                <?php endforeach; ?>
                            </dl>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="meta">The weather records for this day didn't survive the trip.</p>
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
                                        <img src="<?= e(safe_url($leader['image'])) ?>" alt="" width="320" height="320" loading="lazy">
                                    <?php endif; ?>
                                    <figcaption><?= e($title) ?><strong><?= e($leader['name']) ?></strong></figcaption>
                                </figure>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="scene scene-numbers">
            <p class="eyebrow">The readout</p>
            <h2 data-split>Your day in numbers</h2>
            <p class="note" data-reveal>What the machine's dials say about <?= e($birthday->format('j F Y')) ?>.</p>

            <div class="stats">
                <div class="stat" data-reveal>
                    <span class="stat-label">You landed on a</span>
                    <span class="stat-value"><?= e($numbers['weekday']) ?></span>
                </div>
                <div class="stat" data-reveal>
                    <span class="stat-label">Star sign</span>
                    <span class="stat-value"><?= e($numbers['star_sign']) ?></span>
                </div>
                <div class="stat" data-reveal>
                    <span class="stat-label">Chinese zodiac</span>
                    <span class="stat-value">Year of the <?= e($numbers['chinese_zodiac']) ?></span>
                </div>
                <div class="stat" data-reveal>
                    <span class="stat-label">The moon that night</span>
                    <span class="stat-value"><?= e($numbers['moon']['name']) ?></span>
                    <span class="stat-note"><?= e((string) $numbers['moon']['lit']) ?>% lit</span>
                </div>
                <div class="stat stat-wide" data-reveal>
                    <span class="stat-label">Days since you arrived</span>
                    <span class="stat-value stat-big" data-count="<?= e((string) $numbers['days_alive']) ?>" data-group="1"><?= e(number_format($numbers['days_alive'])) ?></span>
                </div>
                <div class="stat stat-wide" data-reveal>
                    <span class="stat-label">Your 10,000th day</span>
                    <span class="stat-value"><?= e($tenThousandth->format('j F Y')) ?></span>
                    <span class="stat-note">
                        <?php if ($tenThousandthAway > 0): ?>
                            That's <?= e(number_format($tenThousandthAway)) ?> days from now. Mark the calendar.
                        <?php elseif ($tenThousandthAway === 0): ?>
                            That's today. Happy 10,000th!
                        <?php else: ?>
                            You passed it <?= e(number_format(abs($tenThousandthAway))) ?> days ago.
                        <?php endif; ?>
                    </span>
                </div>
                <?php $prices = $day['prices']; ?>
                <?php if ($prices['stamp_cents'] !== null): ?>
                    <div class="stat stat-wide" data-reveal>
                        <span class="stat-label">A first-class stamp, in the US</span>
                        <span class="stat-value"><?= e((string) $prices['stamp_cents']) ?>¢</span>
                        <span class="stat-note"><?= $prices['stamp_cents'] === $prices['stamp_cents_now'] ? 'Still the going rate today.' : 'Today it costs ' . e((string) $prices['stamp_cents_now']) . '¢.' ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($prices['wage'] !== null): ?>
                    <div class="stat stat-wide" data-reveal>
                        <span class="stat-label">US federal minimum wage</span>
                        <span class="stat-value">$<?= e(number_format($prices['wage'], 2)) ?> an hour</span>
                        <span class="stat-note"><?= $prices['wage'] === $prices['wage_now'] ? 'Still the going rate today.' : 'Today it\'s $' . e(number_format($prices['wage_now'], 2)) . '.' ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="scene scene-events">
            <div class="ghost" aria-hidden="true" data-speed="0.8"><span><?= e($birthday->format('Y')) ?> · <?= e($birthday->format('Y')) ?> · <?= e($birthday->format('Y')) ?> · <?= e($birthday->format('Y')) ?></span></div>
            <p class="eyebrow">The world that day</p>
            <h2 data-split>What was going on</h2>
            <p class="note" data-reveal>Headlines Wikipedia lists for this date in <?= e($birthday->format('Y')) ?>, in the order its monthly article gives them.</p>

            <?php if ($day['events'] === []): ?>
                <p class="meta">The history books are quiet about this date in <?= e($birthday->format('Y')) ?>.</p>
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
                <p class="meta" style="margin-inline: var(--gutter)">The machine couldn't find anyone born on this date right now.</p>
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

        <section class="scene scene-also">
            <p class="eyebrow">Same date, other years</p>
            <h2 data-split>Also on this day</h2>

            <?php if ($day['also']['deaths'] === [] && $day['also']['holidays'] === []): ?>
                <p class="meta">The calendar pages for this date came loose in the trip. Try the machine again later.</p>
            <?php endif; ?>

            <div class="also-grid">
                <?php if ($day['also']['deaths'] !== []): ?>
                    <div class="also-block" data-reveal>
                        <p class="eyebrow">Gone on this day</p>
                        <ul class="also-list">
                            <?php foreach ($day['also']['deaths'] as $person): ?>
                                <li>
                                    <span class="also-year"><?= e(year_label($person['year'] ?? null)) ?></span>
                                    <span class="also-body">
                                        <?php if (safe_url($person['url'] ?? null) !== null): ?>
                                            <a href="<?= e(safe_url($person['url'])) ?>" rel="noopener"><?= e($person['name']) ?></a>
                                        <?php else: ?>
                                            <strong><?= e($person['name']) ?></strong>
                                        <?php endif; ?>
                                        <?php if (!empty($person['known_for'])): ?>
                                            <span class="meta"><?= e(ucfirst($person['known_for'])) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($day['also']['holidays'] !== []): ?>
                    <div class="also-block" data-reveal>
                        <p class="eyebrow">On the calendar</p>
                        <ul class="also-list also-holidays">
                            <?php foreach ($day['also']['holidays'] as $holiday): ?>
                                <li>
                                    <?php if (safe_url($holiday['url'] ?? null) !== null): ?>
                                        <a href="<?= e(safe_url($holiday['url'])) ?>" rel="noopener"><?= e($holiday['text']) ?></a>
                                    <?php else: ?>
                                        <?= e($holiday['text']) ?>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
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
                        This song didn't make the trip: no music video is linked for it yet. You can
                        <a href="<?= e($searchUrl) ?>" rel="noopener">search for it on YouTube</a>.
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <p class="meta">The Billboard Hot 100 only starts in 1958, so there's no chart song waiting for you on this date.</p>
            <?php endif; ?>
        </section>

        <section class="scene scene-culture">
            <p class="eyebrow">Around town</p>
            <h2 data-split>Cinemas and record shops</h2>
            <p class="note" data-reveal>The film at the top of the US box office and the album at the top of the Billboard 200 around your date.</p>

            <div class="culture-grid">
                <?php $movie = $day['culture']['movie']; ?>
                <article class="culture-card" data-reveal>
                    <p class="eyebrow">At the movies</p>
                    <?php if ($movie !== null): ?>
                        <p class="culture-title"><?= e($movie['film']) ?></p>
                        <p class="meta">
                            No. 1 at the US box office, weekend ending <?= e((new DateTimeImmutable($movie['weekend_end']))->format('j F Y')) ?>
                            <?= $movie['gross'] !== null ? ' · ' . e($movie['gross']) : '' ?>
                        </p>
                        <p><a href="https://www.youtube.com/results?search_query=<?= e(rawurlencode($movie['film'] . ' trailer')) ?>" rel="noopener">Find the trailer on YouTube</a></p>
                    <?php else: ?>
                        <p class="meta">The cinema listings for this date didn't make the trip.</p>
                    <?php endif; ?>
                </article>

                <?php $album = $day['culture']['album']; ?>
                <article class="culture-card" data-reveal>
                    <p class="eyebrow">In the record shop</p>
                    <?php if ($album !== null): ?>
                        <p class="culture-title"><?= e($album['album']) ?></p>
                        <p class="meta">
                            by <?= e($album['artist']) ?>
                            · No. 1 on the <?= e($album['chart']) ?>, week of <?= e((new DateTimeImmutable($album['chart_date']))->format('j F Y')) ?>
                        </p>
                        <p><a href="https://www.youtube.com/results?search_query=<?= e(rawurlencode($album['album'] . ' ' . $album['artist'])) ?>" rel="noopener">Find it on YouTube</a></p>
                    <?php else: ?>
                        <p class="meta">The record shop's chart for this date didn't make the trip.</p>
                    <?php endif; ?>
                </article>
            </div>
        </section>

        <section class="scene scene-milestones">
            <p class="eyebrow">Fast-forward</p>
            <h2 data-split>Growing up on the charts</h2>

            <?php if ($day['milestones'] === []): ?>
                <p class="meta">The machine only visits birthdays that have already happened, and its chart begins in 1958. No stops to make here yet.</p>
            <?php else: ?>
                <p class="note" data-reveal>The number one song on the Billboard Hot 100 each time your birthday came around.</p>
                <div class="milestones">
                    <?php foreach ($day['milestones'] as $stop): ?>
                        <article class="milestone" data-reveal>
                            <span class="milestone-age"><?= e((string) $stop['age']) ?></span>
                            <p class="milestone-date"><?= e((new DateTimeImmutable($stop['date']))->format('j F Y')) ?></p>
                            <p class="milestone-song">"<?= e($stop['song']) ?>"</p>
                            <p class="meta">by <?= e($stop['artist']) ?></p>
                            <p><a href="https://www.youtube.com/results?search_query=<?= e(rawurlencode($stop['song'] . ' ' . $stop['artist'])) ?>" rel="noopener">Find it on YouTube</a></p>
                        </article>
                    <?php endforeach; ?>
                </div>
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

        <!-- A new random day for the same name. random.js fills in the date and place, then sends this to /start. -->
        <form class="again" method="post" action="/start">
            <?= csrf_field() ?>
            <input type="hidden" name="name" value="<?= e($day['name']) ?>">
            <input type="hidden" name="birthday" value="">
            <input type="hidden" name="place_name" value="">
            <input type="hidden" name="place_region" value="">
            <input type="hidden" name="place_country" value="">
            <input type="hidden" name="place_country_code" value="">
            <input type="hidden" name="place_latitude" value="">
            <input type="hidden" name="place_longitude" value="">
            <input type="hidden" name="place_timezone" value="">
            <p>Fancy another trip?</p>
            <button type="button" class="button-secondary" id="random-again" hidden>Surprise me!</button>
        </form>

        <p class="admin-link"><a href="/">Back to the controls</a></p>
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
    <script src="assets/random.js"></script>
</body>
</html>
