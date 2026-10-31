<?php
declare(strict_types=1);

// What was on in the cinemas and record shops: the US number one album and the US number one film
// for a date, both read from Wikipedia's yearly list pages.

// The number one film at the US box office for the weekend (or week) that ended on or after the date.
// Returns ['film', 'weekend_end', 'gross'], or null when Wikipedia has no list for that year.
function box_office_number_one_on(DateTimeImmutable $date): ?array
{
    $target = $date->format('Y-m-d');
    $rows = box_office_year_rows((int) $date->format('Y'));

    if ($rows === []) {
        return null;
    }

    $pick = null;

    foreach ($rows as $row) {
        if ($row['date'] >= $target) {
            $pick = $row;
            break;
        }
    }

    // Late December can fall after the year's last weekend, so use that last one.
    $pick ??= end($rows);

    return [
        'film' => $pick['film'],
        'weekend_end' => $pick['date'],
        'gross' => $pick['gross'],
    ];
}

// The number one album on the Billboard 200 in effect on the date (weekly, from the mid-1950s).
// Returns ['album', 'artist', 'chart_date', 'chart'], or null when the list has nothing for that date.
function billboard_album_number_one_on(DateTimeImmutable $date): ?array
{
    $year = (int) $date->format('Y');
    $target = $date->format('Y-m-d');
    $best = null;

    foreach (album_year_rows($year) as $row) {
        if ($row['date'] <= $target) {
            $best = $row;
        }
    }

    // The first chart of a year comes after 1 January, so fall back to the last chart of the year before.
    if ($best === null) {
        $previous = album_year_rows($year - 1);
        $best = $previous === [] ? null : end($previous);
    }

    if ($best === null) {
        return null;
    }

    return [
        'album' => clean_chart_text($best['song']),
        'artist' => clean_chart_text($best['artist']),
        'chart_date' => $best['date'],
        'chart' => 'Billboard 200 (US)',
    ];
}

// Weekly number one albums for a year: [['date' => 'Y-m-d', 'song' => album, 'artist' => ...], ...].
// The rows use the singles parser, so an album sits in the 'song' key.
function album_year_rows(int $year): array
{
    $rows = cache_remember("billboard-albums-v2:$year", app_config()['cache_ttl']['chart'], function () use ($year) {
        foreach (["List of Billboard 200 number-one albums of $year", "List of Billboard number-one albums of $year"] as $title) {
            $html = wiki_page_html($title);

            if ($html === null) {
                continue;
            }

            $rows = billboard_parse_rows($html, $year, 0);

            if ($rows !== []) {
                return $rows;
            }
        }

        // Nothing found. Null isn't cached, so this is retried later.
        return null;
    });

    return is_array($rows) ? $rows : [];
}

// Weekly (or weekend) number one films for a year: [['date' => 'Y-m-d', 'film' => ..., 'gross' => '$19,050,675'], ...].
function box_office_year_rows(int $year): array
{
    $rows = cache_remember("boxoffice-v2:$year", app_config()['cache_ttl']['chart'], function () use ($year) {
        $html = wiki_page_html("List of $year box office number-one films in the United States");

        return $html === null ? null : box_office_parse_rows($html);
    });

    return is_array($rows) ? $rows : [];
}

// The rendered HTML of a Wikipedia article, or null if it doesn't exist.
function wiki_page_html(string $title): ?string
{
    $data = http_get_json('https://en.wikipedia.org/w/api.php?' . http_build_query([
        'action' => 'parse',
        'page' => $title,
        'prop' => 'text',
        'redirects' => 1,
        'format' => 'json',
        'formatversion' => 2,
    ]));

    $html = $data['parse']['text'] ?? null;

    return is_string($html) ? $html : null;
}

// Reads the list table: # | date | film | gross | notes. A film that held on for several weeks spans
// those rows with rowspan, so its name carries down.
function box_office_parse_rows(string $html): array
{
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $table = null;

    foreach ($xpath->query('//table') as $candidate) {
        $isWikitable = str_contains(' ' . $candidate->getAttribute('class') . ' ', ' wikitable ');
        $header = $xpath->query('.//tr', $candidate)->item(0)?->textContent ?? '';

        if ($isWikitable && str_contains($header, 'Film')) {
            $table = $candidate;
            break;
        }
    }

    if ($table === null) {
        return [];
    }

    $carry = [];
    $rows = [];

    foreach ($xpath->query('.//tr', $table) as $tr) {
        $cells = [];
        $col = 0;

        foreach ($tr->childNodes as $cell) {
            if (!in_array($cell->nodeName, ['td', 'th'], true)) {
                continue;
            }

            while (isset($carry[$col]) && $carry[$col]['left'] > 0) {
                $cells[$col] = $carry[$col]['text'];
                $carry[$col]['left']--;
                $col++;
            }

            $text = trim(preg_replace('/\s+/', ' ', preg_replace('/\[[^\]]*\]/', '', $cell->textContent)));
            $span = (int) ($cell->attributes?->getNamedItem('rowspan')?->nodeValue ?? 1);
            $cells[$col] = $text;

            if ($span > 1) {
                $carry[$col] = ['text' => $text, 'left' => $span - 1];
            }

            $col++;
        }

        // A row that holds only the date is covered entirely by rowspans from above.
        while (isset($carry[$col]) && $carry[$col]['left'] > 0) {
            $cells[$col] = $carry[$col]['text'];
            $carry[$col]['left']--;
            $col++;
        }

        // Columns: 0 = number, 1 = date, 2 = film, 3 = gross.
        if (!isset($cells[1], $cells[2])) {
            continue;
        }

        $date = DateTimeImmutable::createFromFormat('F j, Y', $cells[1]);

        // Header rows and stray rows have no date.
        if ($date === false || $cells[2] === '') {
            continue;
        }

        $rows[] = [
            'date' => $date->format('Y-m-d'),
            'film' => trim($cells[2], " \t†‡*"),
            // Older lists put notes where the gross goes, so only keep an actual dollar figure.
            'gross' => isset($cells[3]) && preg_match('/^\$[\d,.]+( million)?$/', $cells[3]) ? $cells[3] : null,
        ];
    }

    return $rows;
}

// Both at once, for the day builder. Either can be null.
function culture_on(DateTimeImmutable $date): array
{
    return [
        'movie' => box_office_number_one_on($date),
        'album' => billboard_album_number_one_on($date),
    ];
}
