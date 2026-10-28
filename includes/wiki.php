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
        $key = 'wikidata-sparql:' . md5(implode('|', $batch));

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
                       (COUNT(DISTINCT ?article) AS ?sitelinks)
                WHERE {
                  VALUES ?item { $values }
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
        $entity = $entities[$id] ?? ['label' => null, 'description' => null, 'sitelinks' => 0, 'image' => null, 'birthplace' => null];

        if ($entity['label'] !== null) {
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
            'image' => $entity['image'] ?? ($candidate['page']['thumbnail']['source'] ?? null),
            'url' => $candidate['page']['content_urls']['desktop']['page'] ?? null,
        ];
    }

    return $people;
}

// The most famous events on this month and day, ranked by Wikidata sitelinks.
// Events without a Wikidata match rank last, so the list still fills up when Wikidata has no entry.
// Wikipedia gives no time of day for most events, so 'time' is null.
function wiki_top_events(int $month, int $day, int $count = 12): array
{
    $candidates = [];

    foreach (wiki_feed('events', $month, $day) as $item) {
        $page = $item['pages'][0] ?? null;
        $id = $page['wikibase_item'] ?? null;

        $candidates[] = [
            'id' => $id,
            'item' => $item,
            'page' => $page,
        ];
    }

    $entities = wikidata_entities(array_column($candidates, 'id'));

    foreach ($candidates as $i => $candidate) {
        $candidates[$i]['entity'] = $entities[$candidate['id']] ?? ['sitelinks' => 0, 'image' => null, 'location' => null];
    }

    usort($candidates, fn ($a, $b) => $b['entity']['sitelinks'] <=> $a['entity']['sitelinks']);

    $events = [];

    foreach (array_slice($candidates, 0, $count) as $candidate) {
        $events[] = [
            'year' => $candidate['item']['year'] ?? null,
            'text' => $candidate['item']['text'] ?? null,
            'location' => $candidate['entity']['location'],
            'time' => null,
            'image' => $candidate['entity']['image'] ?? ($candidate['page']['thumbnail']['source'] ?? null),
            'url' => $candidate['page']['content_urls']['desktop']['page'] ?? null,
        ];
    }

    // Keep the 12 most famous, then show them oldest first so they follow the day's timeline.
    usort($events, fn ($a, $b) => ($a['year'] ?? PHP_INT_MAX) <=> ($b['year'] ?? PHP_INT_MAX));

    return $events;
}

// Wikipedia summary for a city, found by searching "City Country".
function wiki_city_summary(string $city, string $country): ?array
{
    $key = 'city:' . mb_strtolower($city . '|' . $country);

    return cache_remember($key, app_config()['cache_ttl']['wiki'], function () use ($city, $country) {
        $search = http_get_json('https://en.wikipedia.org/w/api.php?' . http_build_query([
            'action' => 'opensearch',
            'search' => "$city $country",
            'limit' => 1,
            'namespace' => 0,
            'format' => 'json',
        ]));

        $title = $search[1][0] ?? null;

        if ($title === null) {
            return null;
        }

        $summary = http_get_json('https://en.wikipedia.org/api/rest_v1/page/summary/'
            . rawurlencode(str_replace(' ', '_', $title)));

        if ($summary === null) {
            return null;
        }

        return [
            'title' => $summary['title'] ?? $title,
            'extract' => $summary['extract'] ?? null,
            'image' => $summary['thumbnail']['source'] ?? null,
            'url' => $summary['content_urls']['desktop']['page'] ?? null,
        ];
    });
}
