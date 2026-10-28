<?php
declare(strict_types=1);

// Look up a city and return the best match for the given country, or null if none match.
// Uses the Open-Meteo geocoding API (free, no key). This is the fallback when the place wasn't picked from the list.
function geocode_city(string $city, string $country): ?array
{
    $query = http_build_query([
        'name' => $city,
        'count' => 10,
        'language' => 'en',
        'format' => 'json',
    ]);

    $ch = curl_init('https://geocoding-api.open-meteo.com/v1/search?' . $query);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'BornHere/0.1 (portfolio project)',
    ]);

    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($body === false || $status !== 200) {
        return null;
    }

    $data = json_decode($body, true);
    $results = is_array($data['results'] ?? null) ? $data['results'] : [];

    $wanted = mb_strtolower(trim($country));

    foreach ($results as $place) {
        $name = mb_strtolower((string) ($place['country'] ?? ''));
        $code = mb_strtolower((string) ($place['country_code'] ?? ''));

        if ($wanted === $name || $wanted === $code) {
            return [
                'name' => (string) $place['name'],
                'region' => (string) ($place['admin1'] ?? ''),
                'country' => (string) $place['country'],
                'country_code' => (string) $place['country_code'],
                'latitude' => (float) $place['latitude'],
                'longitude' => (float) $place['longitude'],
                'timezone' => (string) $place['timezone'],
            ];
        }
    }

    return null;
}

// A place picked from the suggestion list, read from the submitted form.
// Coordinates and timezone are checked, since they come from the browser. Returns null if anything is off.
function place_from_selection(array $post): ?array
{
    $latitude = filter_var($post['place_latitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($post['place_longitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $timezone = trim((string) ($post['place_timezone'] ?? ''));
    $name = trim((string) ($post['place_name'] ?? ''));

    if ($latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        return null;
    }

    if (!in_array($timezone, DateTimeZone::listIdentifiers(), true) || $name === '' || mb_strlen($name) > 120) {
        return null;
    }

    return [
        'name' => $name,
        'region' => mb_substr(trim((string) ($post['place_region'] ?? '')), 0, 120),
        'country' => mb_substr(trim((string) ($post['place_country'] ?? '')), 0, 120),
        'country_code' => mb_substr(trim((string) ($post['place_country_code'] ?? '')), 0, 2),
        'latitude' => $latitude,
        'longitude' => $longitude,
        'timezone' => $timezone,
    ];
}
