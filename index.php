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
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/calendar.css">
    <link rel="stylesheet" href="assets/places.css">
    <script>document.documentElement.classList.add('js');</script>
</head>
<body>
    <main>
        <h1>Born Here</h1>
        <p class="intro">Enter your details to see what the world looked like the day you were born.</p>

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

    <script src="assets/calendar.js"></script>
    <script src="assets/places.js"></script>
</body>
</html>
