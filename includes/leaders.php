<?php
declare(strict_types=1);

require_once __DIR__ . '/app.php';
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/wiki.php';

// The head of state and head of government of a country on a date, from Wikidata.
// Returns ['head_of_state' => ?array, 'head_of_government' => ?array], each ['name' => string, 'image' => ?string] or null when Wikidata has no holder for that date.
// These are the official offices, so a party leader who held no office can be missing.
function country_leaders(string $countryCode, DateTimeImmutable $date): array
{
    $countryCode = strtoupper(trim($countryCode));

    if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
        return ['head_of_state' => null, 'head_of_government' => null];
    }

    $when = $date->format('Y-m-d\TH:i:s\Z');
    // The key is versioned because the rows now include each leader's photo.
    $rows = cache_remember("leaders-v2:$countryCode|$when", app_config()['cache_ttl']['wiki'], function () use ($countryCode, $when) {
        $query = <<<SPARQL
            PREFIX wd: <http://www.wikidata.org/entity/>
            PREFIX wdt: <http://www.wikidata.org/prop/direct/>
            PREFIX p: <http://www.wikidata.org/prop/>
            PREFIX ps: <http://www.wikidata.org/prop/statement/>
            PREFIX pq: <http://www.wikidata.org/prop/qualifier/>
            PREFIX bd: <http://www.bigdata.com/rdf#>
            PREFIX wikibase: <http://wikiba.se/ontology#>
            PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>
            SELECT ?role ?leaderLabel ?leaderImage ?start ?end WHERE {
              ?country wdt:P297 "$countryCode" .
              {
                ?country p:P35 ?s . ?s ps:P35 ?leader . BIND("state" AS ?role)
              } UNION {
                ?country p:P6 ?s . ?s ps:P6 ?leader . BIND("government" AS ?role)
              }
              OPTIONAL { ?leader wdt:P18 ?leaderImage . }
              OPTIONAL { ?s pq:P580 ?start . }
              OPTIONAL { ?s pq:P582 ?end . }
              FILTER(!BOUND(?start) || ?start <= "$when"^^xsd:dateTime)
              FILTER(BOUND(?start) || BOUND(?end))
              FILTER(!BOUND(?end) || ?end >= "$when"^^xsd:dateTime)
              SERVICE wikibase:label { bd:serviceParam wikibase:language "en". }
            }
            SPARQL;

        $data = http_get_json('https://query.wikidata.org/sparql?' . http_build_query([
            'query' => $query,
            'format' => 'json',
        ]), 90);

        return isset($data['results']['bindings']) ? $data['results']['bindings'] : null;
    });

    $holders = ['state' => [], 'government' => []];

    foreach ($rows as $row) {
        $role = $row['role']['value'] ?? null;
        $label = $row['leaderLabel']['value'] ?? null;

        // Without an English label, Wikidata returns the item ID (e.g. Q123), which isn't a name.
        if (!isset($holders[$role]) || $label === null || preg_match('/^Q\d+$/', $label)) {
            continue;
        }

        $image = $row['leaderImage']['value'] ?? null;

        $holders[$role][] = [
            'label' => $label,
            'image' => https_url($image !== null ? $image . '?width=800' : null),
            'start' => $row['start']['value'] ?? null,
            'end' => $row['end']['value'] ?? null,
        ];
    }

    return [
        'head_of_state' => pick_holder($holders['state']),
        'head_of_government' => pick_holder($holders['government']),
    ];
}

// Picks the holder whose term started most recently. If no term has a start date, picks the one whose term ends first.
// Returns ['name' => ..., 'image' => ?string], or null when there is no holder.
function pick_holder(array $holders): ?array
{
    $dated = array_values(array_filter($holders, fn ($h) => $h['start'] !== null));

    if ($dated !== []) {
        usort($dated, fn ($a, $b) => strcmp($b['start'], $a['start']));
        $holder = $dated[0];
    } else {
        usort($holders, fn ($a, $b) => strcmp($a['end'] ?? '9999', $b['end'] ?? '9999'));
        $holder = $holders[0] ?? null;
    }

    return $holder === null ? null : ['name' => $holder['label'], 'image' => $holder['image']];
}
