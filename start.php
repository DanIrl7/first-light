<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/geocode.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

verify_csrf();

$name = trim((string) ($_POST['name'] ?? ''));
$birthday = trim((string) ($_POST['birthday'] ?? ''));

if ($name === '' || mb_strlen($name) > 80) {
    set_flash('Please enter a name (up to 80 characters).');
    redirect('index.php');
}

$date = DateTimeImmutable::createFromFormat('!Y-m-d', $birthday);
$earliest = new DateTimeImmutable('1940-01-01');
$today = new DateTimeImmutable('today');

if ($date === false || $date->format('Y-m-d') !== $birthday || $date < $earliest || $date > $today) {
    set_flash('Please pick a birthday between 1 January 1940 and today.');
    redirect('index.php');
}

// A place chosen from the suggestion list is used as-is. Otherwise, look up the typed city and country.
if (($_POST['place_latitude'] ?? '') !== '') {
    $place = place_from_selection($_POST);

    if ($place === null) {
        set_flash('That place couldn\'t be read. Please choose it from the list again.');
        redirect('index.php');
    }
} else {
    $city = trim((string) ($_POST['city'] ?? ''));
    $country = trim((string) ($_POST['country'] ?? ''));

    if ($city === '' || $country === '') {
        set_flash('Please choose a birth place from the list, or enter a city and a country.');
        redirect('index.php');
    }

    $place = geocode_city($city, $country);

    if ($place === null) {
        set_flash('We could not find "' . $city . '" in "' . $country . '". Check the spelling, or try a larger nearby city.');
        redirect('index.php');
    }
}

// Keep only what the next steps need. Name and birthday stay in the session, never in the URL.
$_SESSION['profile'] = [
    'name' => $name,
    'birthday' => $date->format('Y-m-d'),
    'place' => $place,
];

redirect('result.php');
