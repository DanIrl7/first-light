<?php
declare(strict_types=1);

// Wikipedia's "On this day" feed for a month and day. $kind is 'births' or 'events'.
function wiki_feed(string $kind, int $month, int $day): array
{
    $key = sprintf('feed:%s:%02d-%02d', $kind, $month, $day);
    $url = sprintf('https://en.wikipedia.org/api/rest_v1/feed/onthisday/%s/%02d/%02d', $kind, $month, $day);

    $items = cache_remember($key, app_config()['cache_ttl']['wiki'], function () use ($url, $kind) {
        $data = http_get_json($url);

        return $data === null ? null : ($data[$kind] ?? []);
    });

    return is_array($items) ? $items : [];
}

// Wikidata facts for many items at once, via one SPARQL query per batch of 50.
// Returns an array keyed by Wikidata ID (e.g. 'Q770789') with: label, description, sitelinks,
// image (Commons URL), birthplace (label), location (label).
// SPARQL is used instead of wbgetentities because full entities are too large to fetch in batches.
function wikidata_entities(array $ids): array
{
    $ids = array_values(array_unique(array_filter($ids)));
    $found = [];

    foreach (array_chunk($ids, 50) as $batch) {
        // The key is versioned so entries cached before the 'human' flag existed are not reused.
        $key = 'wikidata-sparql-v2:' . md5(implode('|', $batch));

        $part = cache_remember($key, app_config()['cache_ttl']['wiki'], function () use ($batch) {
            $values = implode(' ', array_map(fn ($id) => "wd:$id", $batch));

            $query = <<<SPARQL
                PREFIX wd: <http://www.wikidata.org/entity/>
                PREFIX wdt: <http://www.wikidata.org/prop/direct/>
                PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
                PREFIX schema: <http://schema.org/>
                SELECT ?item
                       (SAMPLE(?label) AS ?itemLabel)
                       (SAMPLE(?desc) AS ?itemDesc)
                       (SAMPLE(?image) AS ?itemImage)
                       (SAMPLE(?birthplaceLabel) AS ?birthplaceName)
                       (SAMPLE(?locationLabel) AS ?locationName)
                       (SAMPLE(?isHuman) AS ?human)
                       (COUNT(DISTINCT ?article) AS ?sitelinks)
                WHERE {
                  VALUES ?item { $values }
                  BIND(EXISTS { ?item wdt:P31 wd:Q5 } AS ?isHuman)
                  OPTIONAL { ?item rdfs:label ?label FILTER(LANG(?label) = "en") }
                  OPTIONAL { ?item schema:description ?desc FILTER(LANG(?desc) = "en") }
                  OPTIONAL { ?item wdt:P18 ?image }
                  OPTIONAL { ?item wdt:P19 ?birthplace . ?birthplace rdfs:label ?birthplaceLabel FILTER(LANG(?birthplaceLabel) = "en") }
                  OPTIONAL { ?item wdt:P276 ?location . ?location rdfs:label ?locationLabel FILTER(LANG(?locationLabel) = "en") }
                  OPTIONAL { ?article schema:about ?item . }
                }
                GROUP BY ?item
                SPARQL;

            $data = http_get_json('https://query.wikidata.org/sparql?' . http_build_query([
                'query' => $query,
                'format' => 'json',
            ]), 90);

            if ($data === null || !isset($data['results']['bindings'])) {
                return null;
            }

            $reduced = [];

            foreach ($data['results']['bindings'] as $row) {
                $id = basename($row['item']['value']);
                $image = $row['itemImage']['value'] ?? null;

                $reduced[$id] = [
                    'label' => $row['itemLabel']['value'] ?? null,
                    'description' => $row['itemDesc']['value'] ?? null,
                    'sitelinks' => (int) ($row['sitelinks']['value'] ?? 0),
                    'image' => $image !== null ? $image . '?width=800' : null,
                    'birthplace' => $row['birthplaceName']['value'] ?? null,
                    'location' => $row['locationName']['value'] ?? null,
                    'human' => ($row['human']['value'] ?? 'false') === 'true',
                ];
            }

            return $reduced;
        });

        if (is_array($part)) {
            $found += $part;
        }
    }

    return $found;
}

// Wikidata gives Commons photos as http:// links, but the page only shows https:// images. Commons serves https too.
function https_url(?string $url): ?string
{
    return $url === null ? null : preg_replace('#^http://#', 'https://', $url);
}

// The most famous people born on this month and day, ranked by Wikidata sitelinks.
function wiki_top_people(int $month, int $day, int $count = 5): array
{
    $candidates = [];

    foreach (wiki_feed('births', $month, $day) as $item) {
        $page = $item['pages'][0] ?? null;
        $id = $page['wikibase_item'] ?? null;

        if ($id !== null) {
            $candidates[$id] = ['year' => $item['year'] ?? null, 'page' => $page];
        }
    }

    $entities = wikidata_entities(array_keys($candidates));
    $ranked = [];

    foreach ($candidates as $id => $candidate) {
        $entity = $entities[$id] ?? ['label' => null, 'description' => null, 'sitelinks' => 0, 'image' => null, 'birthplace' => null, 'human' => false];

        // Only people: the feed also links years, days and other non-person items.
        if ($entity['label'] !== null && $entity['human']) {
            $ranked[] = [$entity, $candidate];
        }
    }

    usort($ranked, fn ($a, $b) => $b[0]['sitelinks'] <=> $a[0]['sitelinks']);

    $people = [];

    foreach (array_slice($ranked, 0, $count) as [$entity, $candidate]) {
        $people[] = [
            'name' => $entity['label'],
            'born_year' => $candidate['year'],
            'known_for' => $entity['description'],
            'birthplace' => $entity['birthplace'],
            'image' => https_url($entity['image'] ?? ($candidate['page']['thumbnail']['source'] ?? null)),
            'url' => $candidate['page']['content_urls']['desktop']['page'] ?? null,
        ];
    }

    return $people;
}

// Events Wikipedia lists for one date in one year, taken from that month's article (e.g. "January 1960").
// Returns the entries in the order the article gives them, or an empty list when it has none.
function wiki_year_events(int $year, int $month, int $day): array
{
    $monthName = (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('F');
    $page = "$monthName $year";

    // The key is versioned because the parsing changed: entries now stop before the births and deaths lists.
    $days = cache_remember('month-events-v3:' . $page, app_config()['cache_ttl']['wiki'], function () use ($page) {
        $data = http_get_json('https://en.wikipedia.org/w/api.php?' . http_build_query([
            'action' => 'parse',
            'page' => $page,
            'prop' => 'wikitext',
            'format' => 'json',
            'formatversion' => 2,
        ]));

        $wikitext = $data['parse']['wikitext'] ?? null;

        return $wikitext === null ? null : wiki_parse_month_events($wikitext);
    });

    $entries = $days[$monthName . ' ' . $day] ?? [];

    return array_map(function ($entry) use ($year, $page) {
        // An event links to the article about it, which is used for the photo and the "Wikipedia" link.
        $article = $entry['page'];

        return [
            'year' => $year,
            'text' => $entry['text'],
            'location' => null,
            'time' => null,
            'image' => $article === null ? null : wiki_page_image($article),
            'url' => 'https://en.wikipedia.org/wiki/' . rawurlencode(str_replace(' ', '_', $article ?? $page)),
        ];
    }, $entries);
}

// The main photo of a Wikipedia article, from its summary. Null when the article has none.
function wiki_page_image(string $title): ?string
{
    $image = cache_remember('page-image:' . md5($title), app_config()['cache_ttl']['wiki'], function () use ($title) {
        $summary = http_get_json('https://en.wikipedia.org/api/rest_v1/page/summary/'
            . rawurlencode(str_replace(' ', '_', $title)));

        if ($summary === null) {
            return null;
        }

        // 'none' is cached for articles without a photo, so they aren't looked up again.
        return $summary['thumbnail']['source'] ?? 'none';
    });

    return is_string($image) && $image !== 'none' ? $image : null;
}

// The first article a piece of wikitext links to, e.g. "Reserve Bank of Australia" for [[Reserve Bank of Australia]].
// Links to dates and other namespaces (such as File: or Category:) are skipped.
function wiki_first_link(string $wikitext): ?string
{
    preg_match_all('/\[\[([^\]|#]+)(?:#[^\]|]*)?(?:\|[^\]]*)?\]\]/', $wikitext, $matches);

    foreach ($matches[1] as $target) {
        $target = trim($target);

        if (str_contains($target, ':') || preg_match('/^[A-Z][a-z]+ \d{1,2}$/', $target)) {
            continue;
        }

        return $target;
    }

    return null;
}

// Splits a month article into days: ['January 14' => ['Event text', ...], ...].
// Each day starts with a heading like "==[[January 14]], 1960 (Thursday)==", followed by bullet points.
function wiki_parse_month_events(string $wikitext): array
{
    $days = [];
    $current = null;
    $inEvents = false;

    foreach (explode("\n", $wikitext) as $line) {
        if (preg_match('/^==\s*\[\[([A-Za-z]+ \d{1,2})\]\]/', $line, $match)) {
            $current = $match[1];
            $days[$current] ??= [];
            $inEvents = true;
        } elseif (str_starts_with($line, '==')) {
            $current = null;
        } elseif ($current !== null && preg_match('/^(\*+)\s*(.+)$/', $line, $match)) {
            $text = wiki_plain_text($match[2]);

            // Each day also lists births, deaths and holidays. Those headings end the events list until the next event.
            if ($match[1] === '*') {
                $inEvents = !preg_match('/^(Born|Died|Births|Deaths|Holidays?|Observances?)\b/i', $text);
            }

            if ($inEvents && $text !== '') {
                $days[$current][] = ['text' => $text, 'page' => wiki_first_link($match[2])];
            }
        }
    }

    return $days;
}

// Turns wikitext into plain text: drops references, templates and markup, and keeps the link text.
function wiki_plain_text(string $wikitext): string
{
    $text = preg_replace(['/<ref[^>]*\/>/', '/<ref[^>]*>.*?<\/ref>/s', '/<!--.*?-->/s'], '', $wikitext);
    $text = preg_replace('/\{\{(?:nowrap|nobr)\|([^{}]*)\}\}/i', '$1', $text);

    // Templates can nest, so keep removing the innermost ones until none are left.
    do {
        $text = preg_replace('/\{\{[^{}]*\}\}/', '', $text, -1, $count);
    } while ($count > 0);

    $text = preg_replace('/\[\[(?:[^\]|]*\|)?([^\]]*)\]\]/', '$1', $text);
    $text = preg_replace('/\[https?:\/\/\S+\s+([^\]]+)\]/', '$1', $text);
    $text = preg_replace('/\[https?:\/\/[^\]]*\]/', '', $text);
    $text = preg_replace("/'{2,}/", '', $text);

    return trim(preg_replace('/\s+/', ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

// Wikipedia summary for a city. Only an article that is about this city is used: its title must be the
// city's name, it must not be a disambiguation page, and its summary must mention the country.
function wiki_city_summary(string $city, string $country): ?array
{
    $key = 'city-v2:' . mb_strtolower($city . '|' . $country);

    return cache_remember($key, app_config()['cache_ttl']['wiki'], function () use ($city, $country) {
        $search = http_get_json('https://en.wikipedia.org/w/api.php?' . http_build_query([
            'action' => 'opensearch',
            'search' => $city,
            'limit' => 10,
            'namespace' => 0,
            'format' => 'json',
        ]));

        foreach ($search[1] ?? [] as $title) {
            if (plain_name($title) !== plain_name($city)) {
                continue;
            }

            $summary = http_get_json('https://en.wikipedia.org/api/rest_v1/page/summary/'
                . rawurlencode(str_replace(' ', '_', $title)));

            if ($summary === null || ($summary['type'] ?? '') === 'disambiguation') {
                continue;
            }

            if (!str_contains(fold_text($summary['extract'] ?? ''), fold_text($country))) {
                continue;
            }

            return [
                'title' => $summary['title'] ?? $title,
                'extract' => $summary['extract'] ?? null,
                'image' => $summary['thumbnail']['source'] ?? null,
                'url' => $summary['content_urls']['desktop']['page'] ?? null,
            ];
        }

        return null;
    });
}

// A Wikipedia title without its qualifier, so "Spring (EP)" and "Șpring" compare as "spring".
// Text after a comma is dropped too, so "Springs, Gauteng" stays a different place from "Spring".
function plain_name(string $title): string
{
    return fold_text(trim(preg_replace('/\s*\(.*?\)|,.*$/', '', $title)));
}

// Lowercase with accents removed, so "Șpring" and "Spring" compare equal.
function fold_text(string $text): string
{
    $folded = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

    return mb_strtolower($folded === false ? $text : $folded);
}
