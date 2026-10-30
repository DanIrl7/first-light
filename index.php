<?php
require __DIR__ . '/includes/bootstrap.php';

$flash = pull_flash();
// After a failed submit these hold what the visitor typed and one message per field.
[$old, $errors] = pull_form();
$today = (new DateTimeImmutable('today'))->format('Y-m-d');

function old_value(array $old, string $key): string
{
    return (string) ($old[$key] ?? '');
}

// The visible place box has no value of its own after a reload, so rebuild its text from the chosen place.
$placeText = old_value($old, 'place_search');

if ($placeText === '' && old_value($old, 'place_latitude') !== '') {
    $placeText = implode(', ', array_filter([
        old_value($old, 'place_name'),
        old_value($old, 'place_region'),
        old_value($old, 'place_country'),
    ]));
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>First Light</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&amp;family=Fraunces:ital,wght@0,400;0,700;0,800;1,400;1,600&amp;family=Manrope:wght@300;400;500;600&amp;display=swap">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/calendar.css">
    <link rel="stylesheet" href="assets/places.css">
    <script src="assets/dev-motion.js"></script>
    <script>document.documentElement.classList.add('js');</script>
</head>
<body class="page-start">
    <div class="glow" aria-hidden="true"></div>

    <main>
        <p class="eyebrow" data-intro>A scroll through your first day</p>
        <h1 data-intro>First <em>Light</em></h1>
        <p class="intro" data-intro>Enter your details to see what the world looked like the day you were born.</p>

        <?php if ($flash !== null): ?>
            <p class="flash" role="alert"><?= e($flash) ?></p>
        <?php endif; ?>

        <form method="post" action="/start" class="card" id="born-form" novalidate>
            <?= csrf_field() ?>

            <label>Name
                <input type="text" name="name" id="field-name" maxlength="80" required autocomplete="given-name"
                    value="<?= e(old_value($old, 'name')) ?>"
                    <?= isset($errors['name']) ? 'aria-invalid="true"' : '' ?> aria-describedby="error-name">
            </label>
            <p class="field-error" id="error-name" role="alert" <?= isset($errors['name']) ? '' : 'hidden' ?>><?= e($errors['name'] ?? '') ?></p>

            <label>Birthday
                <input type="date" name="birthday" id="birthday-native" class="native-date" min="1940-01-01" max="<?= e($today) ?>" required
                    value="<?= e(old_value($old, 'birthday')) ?>"
                    <?= isset($errors['birthday']) ? 'aria-invalid="true"' : '' ?> aria-describedby="error-birthday">
            </label>
            <p class="field-error" id="error-birthday" role="alert" <?= isset($errors['birthday']) ? '' : 'hidden' ?>><?= e($errors['birthday'] ?? '') ?></p>
            <small class="hint">We can go back as far as 1 January 1940.</small>

            <label>Birth place
                <input type="text" name="place_search" id="place-search" autocomplete="off" placeholder="Start typing a city" aria-autocomplete="list" aria-controls="place-results" aria-expanded="false"
                    value="<?= e($placeText) ?>"
                    <?= isset($errors['place']) ? 'aria-invalid="true"' : '' ?> aria-describedby="error-place">
            </label>
            <div class="place-results" id="place-results" role="listbox" hidden></div>
            <p class="field-error" id="error-place" role="alert" <?= isset($errors['place']) ? '' : 'hidden' ?>><?= e($errors['place'] ?? '') ?></p>
            <small class="hint">Pick the place from the list, so cities with the same name are told apart.</small>

            <div class="fallback-place">
                <label>Birth city
                    <input type="text" name="city" maxlength="80" value="<?= e(old_value($old, 'city')) ?>">
                </label>

                <label>Birth country
                    <input type="text" name="country" maxlength="80" value="<?= e(old_value($old, 'country')) ?>">
                </label>
                <small class="hint">The country tells apart cities with the same name.</small>
            </div>

            <input type="hidden" name="place_name" id="place_name" value="<?= e(old_value($old, 'place_name')) ?>">
            <input type="hidden" name="place_region" id="place_region" value="<?= e(old_value($old, 'place_region')) ?>">
            <input type="hidden" name="place_country" id="place_country" value="<?= e(old_value($old, 'place_country')) ?>">
            <input type="hidden" name="place_country_code" id="place_country_code" value="<?= e(old_value($old, 'place_country_code')) ?>">
            <input type="hidden" name="place_latitude" id="place_latitude" value="<?= e(old_value($old, 'place_latitude')) ?>">
            <input type="hidden" name="place_longitude" id="place_longitude" value="<?= e(old_value($old, 'place_longitude')) ?>">
            <input type="hidden" name="place_timezone" id="place_timezone" value="<?= e(old_value($old, 'place_timezone')) ?>">

            <button type="submit">Continue</button>
        </form>
    </main>

    <script src="assets/calendar.js"></script>
    <script src="assets/places.js"></script>
    <script src="assets/form.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js"></script>
    <script src="assets/start.js"></script>
</body>
</html>
