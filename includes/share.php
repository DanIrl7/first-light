<?php
declare(strict_types=1);

require_once __DIR__ . '/geocode.php';

// A share link holds the whole profile (name, birthday and place), packed small and made URL-safe, so
// nothing has to be stored: /share?t=TOKEN rebuilds the same day for whoever opens it.
// Anyone can read what is in a link, so only the sender decides to hand one out.
const SHARE_TOKEN_MAX = 1200;
const SHARE_JSON_MAX = 2048;

function share_token(array $profile): string
{
    $place = $profile['place'];

    $json = json_encode([
        'n' => $profile['name'],
        'b' => $profile['birthday'],
        'p' => [
            $place['name'], $place['region'], $place['country'], $place['country_code'],
            $place['latitude'], $place['longitude'], $place['timezone'],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return rtrim(strtr(base64_encode(gzdeflate($json, 9)), '+/', '-_'), '=');
}

// The profile a token holds, or null if it isn't a link this site made (or was cut short or changed).
// Everything is checked again as if it came from the form, because a link is typed by the browser.
function share_profile(string $token): ?array
{
    if ($token === '' || strlen($token) > SHARE_TOKEN_MAX || !preg_match('/^[A-Za-z0-9_-]+$/', $token)) {
        return null;
    }

    $packed = base64_decode(strtr($token, '-_', '+/'), true);
    // The size limit stops a tiny token from unpacking into something huge.
    $json = $packed === false ? false : @gzinflate($packed, SHARE_JSON_MAX);

    if ($json === false) {
        return null;
    }

    $data = json_decode($json, true);

    if (!is_array($data) || !is_string($data['n'] ?? null) || !is_string($data['b'] ?? null)
        || !is_array($data['p'] ?? null) || count($data['p']) !== 7) {
        return null;
    }

    $name = trim($data['n']);

    if ($name === '' || mb_strlen($name) > 80) {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $data['b']);

    if ($date === false || $date->format('Y-m-d') !== $data['b']
        || $date < new DateTimeImmutable('1940-01-01') || $date > new DateTimeImmutable('today')) {
        return null;
    }

    [$placeName, $region, $country, $countryCode, $latitude, $longitude, $timezone] = $data['p'];

    $place = place_from_selection([
        'place_name' => is_string($placeName) ? $placeName : '',
        'place_region' => is_string($region) ? $region : '',
        'place_country' => is_string($country) ? $country : '',
        'place_country_code' => is_string($countryCode) ? $countryCode : '',
        'place_latitude' => is_int($latitude) || is_float($latitude) ? $latitude : null,
        'place_longitude' => is_int($longitude) || is_float($longitude) ? $longitude : null,
        'place_timezone' => is_string($timezone) ? $timezone : '',
    ]);

    return $place === null ? null : ['name' => $name, 'birthday' => $date->format('Y-m-d'), 'place' => $place];
}
