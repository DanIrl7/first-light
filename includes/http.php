<?php
declare(strict_types=1);

// GET a URL and return the body, or null if the request failed or didn't return 200.
function http_get(string $url, array $headers = [], int $timeout = 25): ?string
{
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

    return ($body !== false && $status === 200) ? $body : null;
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
