<?php
declare(strict_types=1);

// Weather at 12:00 local time on a date, plus the day's high, low, rain, wind and sunrise and sunset,
// from the Open-Meteo historical archive (1940 onward).
// Returns null if the archive doesn't have that day yet (very recent dates).
// The day's totals and sun times are null when the archive leaves them out (the sun may not rise or set near the poles).
function weather_at_noon(float $latitude, float $longitude, string $date, string $timezone): ?array
{
    // The key is versioned: entries cached before the daily figures existed hold only the noon reading.
    $key = sprintf('weather-v2:%.3f:%.3f:%s', $latitude, $longitude, $date);

    return cache_remember($key, app_config()['cache_ttl']['weather'], function () use ($latitude, $longitude, $date, $timezone) {
        $data = http_get_json('https://archive-api.open-meteo.com/v1/archive?' . http_build_query([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'start_date' => $date,
            'end_date' => $date,
            'hourly' => 'temperature_2m,weather_code',
            'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_sum,wind_speed_10m_max,sunrise,sunset',
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

        $daily = $data['daily'] ?? [];
        $number = fn (string $field) => isset($daily[$field][0]) ? round((float) $daily[$field][0], 1) : null;
        // Open-Meteo gives local times like "2004-06-01T05:12"; the page only needs the clock part.
        $clock = fn (string $field) => isset($daily[$field][0]) && preg_match('/T(\d{2}:\d{2})$/', (string) $daily[$field][0], $match) ? $match[1] : null;

        return [
            'temperature_c' => round((float) $temperature, 1),
            'condition' => wmo_condition((int) $code),
            'time' => '12:00 local time',
            'high_c' => $number('temperature_2m_max'),
            'low_c' => $number('temperature_2m_min'),
            'rain_mm' => $number('precipitation_sum'),
            'wind_kmh' => $number('wind_speed_10m_max'),
            'sunrise' => $clock('sunrise'),
            'sunset' => $clock('sunset'),
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
