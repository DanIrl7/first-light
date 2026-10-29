<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/wiki.php';
require_once __DIR__ . '/weather.php';
require_once __DIR__ . '/billboard.php';
require_once __DIR__ . '/leaders.php';

// Everything the day page shows, built from a profile: ['name', 'birthday', 'place' => [...]].
// Each part is independent, so one failed source leaves the rest of the page intact.
function build_day(array $profile): array
{
    $place = $profile['place'];
    $date = new DateTimeImmutable($profile['birthday']);
    $month = (int) $date->format('n');
    $day = (int) $date->format('j');

    return [
        'name' => $profile['name'],
        'birthday' => $date->format('Y-m-d'),
        'place' => [
            'name' => $place['name'],
            'region' => $place['region'],
            'country' => $place['country'],
            'timezone' => $place['timezone'],
        ],
        'city' => wiki_city_summary($place['name'], $place['country']),
        'leaders' => country_leaders($place['country_code'] ?? '', $date),
        'weather' => weather_at_noon($place['latitude'], $place['longitude'], $date->format('Y-m-d'), $place['timezone']),
        'song' => billboard_number_one_on($date),
        'people' => wiki_top_people($month, $day),
        'events' => wiki_year_events((int) $date->format('Y'), $month, $day),
    ];
}
