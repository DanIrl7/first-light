<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/geocode.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

verify_csrf();

$name = trim((string) ($_POST['name'] ?? ''));
$birthday = trim((string) ($_POST['birthday'] ?? ''));

// One message per field, so the form can show each next to the box it belongs to.
$errors = [];

if ($name === '' || mb_strlen($name) > 80) {
    $errors['name'] = 'Please enter a name (up to 80 characters).';
}

$earliest = new DateTimeImmutable('1940-01-01');
$today = new DateTimeImmutable('today');
$date = false;

if ($birthday === '') {
    $errors['birthday'] = 'Choose your birthday from the calendar.';
} else {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $birthday);

    if ($date === false || $date->format('Y-m-d') !== $birthday) {
        $errors['birthday'] = 'That date wasn\'t recognised. Please choose it from the calendar again.';
    } elseif ($date < $earliest || $date > $today) {
        $errors['birthday'] = 'Please pick a birthday between 1 January 1940 and today.';
    }
}

// A place chosen from the suggestion list is used as-is. Otherwise, look up the typed city and country.
$place = null;

if (($_POST['place_latitude'] ?? '') !== '') {
    $place = place_from_selection($_POST);

    if ($place === null) {
        $errors['place'] = 'That place couldn\'t be read. Please choose it from the list again.';
    }
} else {
    $city = trim((string) ($_POST['city'] ?? ''));
    $country = trim((string) ($_POST['country'] ?? ''));

    if ($city === '' || $country === '') {
        $errors['place'] = 'Please choose a birth place from the list, or enter a city and a country.';
    } else {
        $place = geocode_city($city, $country);

        if ($place === null) {
            $errors['place'] = 'We could not find "' . $city . '" in "' . $country . '". Check the spelling, or try a larger nearby city.';
        }
    }
}

if ($errors !== []) {
    remember_form($_POST, $errors);
    redirect('index.php');
}

// Keep only what the next steps need. Name and birthday stay in the session, never in the URL.
$_SESSION['profile'] = [
    'name' => $name,
    'birthday' => $date->format('Y-m-d'),
    'place' => $place,
];

redirect('result.php');
