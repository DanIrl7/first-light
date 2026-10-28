<?php
declare(strict_types=1);

// Weather at 12:00 local time on a date, from the Open-Meteo historical archive (1940 onward).
// Returns null if the archive doesn't have that day yet (very recent dates).
function weather_at_noon(float $latitude, float $longitude, string $date, string $timezone): ?array
{
    $key = sprintf('weather:%.3f:%.3f:%s', $latitude, $longitude, $date);

    return cache_remember($key, app_config()['cache_ttl']['weather'], function () use ($latitude, $longitude, $date, $timezone) {
        $data = http_get_json('https://archive-api.open-meteo.com/v1/archive?' . http_build_query([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'start_date' => $date,
            'end_date' => $date,
            'hourly' => 'temperature_2m,weather_code',
            'timezone' => $timezone,
        ]));

        $hours = $data['hourly']['time'] ?? [];
        $index = array_search("{$date}T12:00", $hours, true);

        if ($index === false) {
            return null;
        }

        $temperature = $data['hourly']['temperature_2m'][$index] ?? null;
        $code = $data['hourly']['weather_code'][$index] ?? null;

        if ($temperature === null || $code === null) {
            return null;
        }

        return [
            'temperature_c' => round((float) $temperature, 1),
            'condition' => wmo_condition((int) $code),
            'time' => '12:00 local time',
        ];
    });
}

// Plain-language description of a WMO weather code, as used by Open-Meteo.
function wmo_condition(int $code): string
{
    return match (true) {
        $code === 0 => 'Clear sky',
        $code <= 3 => 'Partly cloudy',
        $code <= 48 => 'Fog',
        $code <= 57 => 'Drizzle',
        $code <= 67 => 'Rain',
        $code <= 77 => 'Snow',
        $code <= 82 => 'Rain showers',
        $code <= 86 => 'Snow showers',
        default => 'Thunderstorm',
    };
}
