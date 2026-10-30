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
// $progress, when given, is called as ($step, 'start') and ($step, 'done') around each slow part,
// so the loading screen can show where the build is.
function build_day(array $profile, ?callable $progress = null): array
{
    $place = $profile['place'];
    $date = new DateTimeImmutable($profile['birthday']);
    $month = (int) $date->format('n');
    $day = (int) $date->format('j');

    $step = function (string $name, callable $work) use ($progress): mixed {
        if ($progress !== null) {
            $progress($name, 'start');
        }

        $result = $work();

        if ($progress !== null) {
            $progress($name, 'done');
        }

        return $result;
    };

    return [
        'name' => $profile['name'],
        'birthday' => $date->format('Y-m-d'),
        'place' => [
            'name' => $place['name'],
            'region' => $place['region'],
            'country' => $place['country'],
            'timezone' => $place['timezone'],
        ],
        'city' => $step('city', fn () => wiki_city_summary($place['name'], $place['country'])),
        'leaders' => $step('leaders', fn () => country_leaders($place['country_code'] ?? '', $date)),
        'weather' => $step('weather', fn () => weather_at_noon($place['latitude'], $place['longitude'], $date->format('Y-m-d'), $place['timezone'])),
        'song' => $step('song', fn () => billboard_number_one_on($date)),
        'people' => $step('people', fn () => wiki_top_people($month, $day)),
        'events' => $step('events', fn () => wiki_year_events((int) $date->format('Y'), $month, $day)),
    ];
}
