<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/geocode.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

verify_csrf();

$name = trim((string) ($_POST['name'] ?? ''));
$birthday = trim((string) ($_POST['birthday'] ?? ''));

// One message per field, so the form can show each next to the box it belongs to.
$errors = [];

if ($name === '' || mb_strlen($name) > 80) {
    $errors['name'] = 'The logbook needs a name (up to 80 characters).';
}

$earliest = new DateTimeImmutable('1940-01-01');
$today = new DateTimeImmutable('today');
$date = false;

if ($birthday === '') {
    $errors['birthday'] = 'Pick a date on the calendar to set the dials.';
} else {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $birthday);

    if ($date === false || $date->format('Y-m-d') !== $birthday) {
        $errors['birthday'] = 'The machine didn\'t recognise that date. Pick it from the calendar again.';
    } elseif ($date < $earliest || $date > $today) {
        $errors['birthday'] = 'That\'s outside the machine\'s range. Pick a date between 1 January 1940 and today.';
    }
}

// A place chosen from the suggestion list is used as-is. Otherwise, look up the typed city and country.
$place = null;

if (($_POST['place_latitude'] ?? '') !== '') {
    $place = place_from_selection($_POST);

    if ($place === null) {
        $errors['place'] = 'The machine couldn\'t lock onto that place. Choose it from the list again.';
    }
} else {
    $city = trim((string) ($_POST['city'] ?? ''));
    $country = trim((string) ($_POST['country'] ?? ''));

    if ($city === '' || $country === '') {
        $errors['place'] = 'Choose a point of arrival from the list, or enter a city and a country.';
    } else {
        $place = geocode_city($city, $country);

        if ($place === null) {
            $errors['place'] = 'The machine couldn\'t find "' . $city . '" in "' . $country . '". Check the spelling, or try a larger city nearby.';
        }
    }
}

if ($errors !== []) {
    remember_form($_POST, $errors);
    redirect('/');
}

// Keep only what the next steps need. Name and birthday stay in the session, never in the URL.
$_SESSION['profile'] = [
    'name' => $name,
    'birthday' => $date->format('Y-m-d'),
    'place' => $place,
];

// The loading screen gathers the day with visible progress, then moves on to /result.
redirect('/loading');
