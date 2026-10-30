<?php
declare(strict_types=1);

// The US Billboard Hot 100 number one that was in effect on a date.
// The chart has weekly data from 1958 onward; earlier dates return null.
function billboard_number_one_on(DateTimeImmutable $date): ?array
{
    $year = (int) $date->format('Y');

    if ($year < 1958) {
        return null;
    }

    $target = $date->format('Y-m-d');
    $best = null;

    foreach (billboard_year_rows($year) as $row) {
        if ($row['date'] <= $target) {
            $best = $row;
        }
    }

    // The first chart of a year often comes after 1 January, so fall back to the last chart of the previous year.
    if ($best === null && $year - 1 >= 1958) {
        $previous = billboard_year_rows($year - 1);
        $best = $previous === [] ? null : end($previous);
    }

    if ($best === null) {
        return null;
    }

    return [
        'song' => clean_chart_text($best['song']),
        'artist' => clean_chart_text($best['artist']),
        'chart_date' => $best['date'],
        'chart' => 'Billboard Hot 100 (US)',
    ];
}

// The number one song on the birthdays that mark growing up. An age is skipped when that birthday is still
// in the future or falls before the chart starts, so the list can be empty.
// Returns [['age' => 13, 'date' => 'Y-m-d', 'song' => ..., 'artist' => ..., 'chart_date' => ...], ...].
function billboard_milestones(DateTimeImmutable $birthday, ?DateTimeImmutable $today = null, array $ages = [13, 16, 18, 21]): array
{
    $today ??= new DateTimeImmutable('today');
    $milestones = [];

    foreach ($ages as $age) {
        $year = (int) $birthday->format('Y') + $age;
        $month = (int) $birthday->format('n');
        $day = (int) $birthday->format('j');

        // A 29 February birthday has no such day in most years, so use the 28th.
        if (!checkdate($month, $day, $year)) {
            $day = 28;
        }

        $date = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));

        if ($date > $today || $year < 1958) {
            continue;
        }

        $song = billboard_number_one_on($date);

        if ($song !== null) {
            $milestones[] = ['age' => $age, 'date' => $date->format('Y-m-d')] + $song;
        }
    }

    return $milestones;
}

// Removes footnote symbols and stray quotes that Billboard's tables include, e.g. "Uptown Funk" †
// Applied on read, so rows cached before this fix are cleaned too.
function clean_chart_text(string $text): string
{
    $text = str_replace(['†', '‡', '*'], '', $text);

    return trim($text, " \t\"“”'");
}

// All weekly number ones for a year, in date order: [['date' => 'Y-m-d', 'song' => ..., 'artist' => ...], ...]
function billboard_year_rows(int $year): array
{
    $rows = cache_remember("billboard:$year", app_config()['cache_ttl']['chart'], function () use ($year) {
        // Most years have one page per year. 1958 is only in a combined page, so try that name next.
        $titles = [
            "List of Billboard Hot 100 number ones of $year",
            "List of Billboard number-one singles of $year",
        ];

        foreach ($titles as $title) {
            $data = http_get_json('https://en.wikipedia.org/w/api.php?' . http_build_query([
                'action' => 'parse',
                'page' => $title,
                'prop' => 'text',
                'format' => 'json',
                'formatversion' => 2,
            ]));

            $html = $data['parse']['text'] ?? null;

            if (!is_string($html)) {
                continue;
            }

            $rows = billboard_parse_rows($html, $year);

            if ($rows !== []) {
                return $rows;
            }
        }

        // Nothing found under any title. Null isn't cached, so this is retried later.
        return null;
    });

    return is_array($rows) ? $rows : [];
}

// Read the weekly table from the rendered page. Song and artist cells use rowspan,
// so values carry down into the rows they span.
function billboard_parse_rows(string $html, int $year): array
{
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $table = null;

    // On combined pages, only look at tables after the "Hot 100" heading, so other charts are skipped.
    $heading = $xpath->query("//*[@id='Hot_100']")->item(0);
    $tables = $heading !== null ? $xpath->query('following::table', $heading) : $xpath->query('//table');

    foreach ($tables as $candidate) {
        $isWikitable = str_contains(' ' . $candidate->getAttribute('class') . ' ', ' wikitable ');

        if ($isWikitable && str_contains($candidate->textContent, 'Issue date')) {
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
            // Some years mark the row number as <th>, so read both cell types.
            // Header rows parse to no date and are skipped below.
            if (!in_array($cell->nodeName, ['td', 'th'], true)) {
                continue;
            }

            // Fill columns still covered by a rowspan from an earlier row.
            while (isset($carry[$col]) && $carry[$col]['left'] > 0) {
                $cells[$col] = $carry[$col]['text'];
                $carry[$col]['left']--;
                $col++;
            }

            $text = trim(preg_replace('/\s+/', ' ', preg_replace('/\[\d+\]/', '', $cell->textContent)));
            $span = (int) ($cell->attributes?->getNamedItem('rowspan')?->nodeValue ?? 1);
            $cells[$col] = $text;

            if ($span > 1) {
                $carry[$col] = ['text' => $text, 'left' => $span - 1];
            }

            $col++;
        }

        // Columns: 0 = No., 1 = issue date, 2 = song, 3 = artist.
        if (!isset($cells[1], $cells[2], $cells[3])) {
            continue;
        }

        $date = DateTimeImmutable::createFromFormat('F j Y', trim($cells[1]) . ' ' . $year);

        if ($date === false) {
            continue;
        }

        $rows[] = [
            'date' => $date->format('Y-m-d'),
            'song' => trim($cells[2], " \t\"“”"),
            'artist' => $cells[3],
        ];
    }

    return $rows;
}
