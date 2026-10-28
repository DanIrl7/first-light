<?php
declare(strict_types=1);

// GET a URL and return the body, or null if the request failed or didn't return 200.
// Retries a few times, since one dropped connection would otherwise look like missing data.
function http_get(string $url, array $headers = [], int $timeout = 25): ?string
{
    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => app_config()['user_agent'],
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body !== false && $status === 200) {
            return $body;
        }

        if ($status === 404) {
            return null; // a missing page won't appear on retry
        }

        usleep(500000 * $attempt);
    }

    return null;
}

function http_get_json(string $url, int $timeout = 25): ?array
{
    $body = http_get($url, ['Accept: application/json'], $timeout);

    if ($body === null) {
        return null;
    }

    $data = json_decode($body, true);

    return is_array($data) ? $data : null;
}
