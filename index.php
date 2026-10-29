<?php
require __DIR__ . '/includes/bootstrap.php';

$flash = pull_flash();
$today = (new DateTimeImmutable('today'))->format('Y-m-d');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Born Here</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&amp;family=JetBrains+Mono:wght@400;500&amp;family=Playfair+Display:ital,wght@0,400;0,700;0,800;1,400;1,600&amp;display=swap">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/calendar.css">
    <link rel="stylesheet" href="assets/places.css">
    <script>document.documentElement.classList.add('js');</script>
</head>
<body class="page-start">
    <div class="glow" aria-hidden="true"></div>

    <main>
        <p class="eyebrow" data-intro>A scroll through your first day</p>
        <h1 data-intro>Born <em>Here</em></h1>
        <p class="intro" data-intro>Enter your details to see what the world looked like the day you were born.</p>

        <?php if ($flash !== null): ?>
            <p class="flash"><?= e($flash) ?></p>
        <?php endif; ?>

        <form method="post" action="start.php" class="card">
            <?= csrf_field() ?>

            <label>Name
                <input type="text" name="name" maxlength="80" required>
            </label>

            <label>Birthday
                <input type="date" name="birthday" id="birthday-native" class="native-date" min="1940-01-01" max="<?= e($today) ?>" required>
            </label>
            <small class="hint">We can go back as far as 1 January 1940.</small>

            <label>Birth place
                <input type="text" id="place-search" autocomplete="off" placeholder="Start typing a city" aria-autocomplete="list" aria-controls="place-results" aria-expanded="false">
            </label>
            <div class="place-results" id="place-results" role="listbox" hidden></div>
            <small class="hint">Pick the place from the list, so cities with the same name are told apart.</small>

            <div class="fallback-place">
                <label>Birth city
                    <input type="text" name="city" maxlength="80">
                </label>

                <label>Birth country
                    <input type="text" name="country" maxlength="80">
                </label>
                <small class="hint">The country tells apart cities with the same name.</small>
            </div>

            <input type="hidden" name="place_name" id="place_name">
            <input type="hidden" name="place_region" id="place_region">
            <input type="hidden" name="place_country" id="place_country">
            <input type="hidden" name="place_country_code" id="place_country_code">
            <input type="hidden" name="place_latitude" id="place_latitude">
            <input type="hidden" name="place_longitude" id="place_longitude">
            <input type="hidden" name="place_timezone" id="place_timezone">

            <button type="submit">Continue</button>
        </form>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="assets/calendar.js"></script>
    <script src="assets/places.js"></script>
    <script src="assets/start.js"></script>
</body>
</html>
