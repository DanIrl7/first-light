<?php
require __DIR__ . '/includes/bootstrap.php';

if (empty($_SESSION['profile'])) {
    set_flash('Enter your details first.');
    redirect('index.php');
}

$profile = $_SESSION['profile'];
$birthday = new DateTimeImmutable($profile['birthday']);

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// Each step matches one that build.php reports. The text names what is being looked up.
$steps = [
    'city' => 'Finding ' . $profile['place']['name'],
    'leaders' => 'Asking who was in charge',
    'weather' => 'Checking the weather at noon',
    'song' => 'Finding the number one song',
    'people' => 'Gathering people born that day',
    'events' => 'Reading what happened',
    'video' => 'Cueing the music video',
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Developing your day · Born Here</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&amp;family=JetBrains+Mono:wght@400;500&amp;family=Playfair+Display:ital,wght@0,400;0,700;0,800;1,400;1,600&amp;display=swap">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/loading.css">
    <script>document.documentElement.classList.add('js');</script>
    <noscript><meta http-equiv="refresh" content="0;url=result.php"></noscript>
</head>
<body class="page-loading">
    <div class="glow" aria-hidden="true"></div>

    <main class="loading" data-csrf="<?= e($_SESSION['csrf']) ?>" data-total="<?= count($steps) ?>">
        <p class="eyebrow"><?= e($profile['place']['name']) ?> · <?= e($birthday->format('j F Y')) ?></p>
        <h1 id="loading-title">Developing your day<span class="dots" aria-hidden="true"></span></h1>
        <p class="intro" id="loading-note">The first time a day is looked up it can take a minute. After that it's quick.</p>

        <div class="meter" role="progressbar" aria-label="Gathering your day" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="loading-meter">
            <span id="loading-bar"></span>
        </div>

        <ol class="steps" id="loading-steps">
            <?php foreach ($steps as $key => $text): ?>
                <li data-step="<?= e($key) ?>" data-state="waiting">
                    <span class="mark" aria-hidden="true"></span>
                    <span class="label"><?= e($text) ?></span>
                </li>
            <?php endforeach; ?>
        </ol>

        <div class="loading-failed" id="loading-failed" hidden>
            <p id="loading-error" role="alert"></p>
            <button type="button" id="loading-retry">Try again</button>
            <a href="index.php">Back to the form</a>
        </div>
    </main>

    <p class="loading-live" id="loading-live" role="status" aria-live="polite"></p>

    <script src="assets/loading.js"></script>
</body>
</html>
