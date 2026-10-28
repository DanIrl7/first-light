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

            <label>Birth city
                <input type="text" name="city" maxlength="80" required>
            </label>

            <label>Birth country
                <input type="text" name="country" maxlength="80" required>
            </label>
            <small class="hint">The country tells apart cities with the same name.</small>

            <button type="submit">Continue</button>
        </form>
    </main>

    <script src="assets/calendar.js"></script>
</body>
</html>
