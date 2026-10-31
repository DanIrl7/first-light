<?php
declare(strict_types=1);

// What a stamp and an hour of work were worth in the United States on a date, next to today's figures.
// Both are fixed tables, so nothing is looked up. Each entry starts on its effective date and lasts until the next.

// First-class letter (1 oz) postage in cents. Source: USPS "Domestic letter rates since 1863".
const STAMP_CENTS = [
    '1932-07-06' => 3, '1958-08-01' => 4, '1963-01-07' => 5, '1968-01-07' => 6, '1971-05-16' => 8,
    '1974-03-02' => 10, '1975-12-31' => 13, '1978-05-29' => 15, '1981-03-22' => 18, '1981-11-01' => 20,
    '1985-02-17' => 22, '1988-04-03' => 25, '1991-02-03' => 29, '1995-01-01' => 32, '1999-01-10' => 33,
    '2001-01-07' => 34, '2002-06-30' => 37, '2006-01-08' => 39, '2007-05-14' => 41, '2008-05-12' => 42,
    '2009-05-11' => 44, '2012-01-22' => 45, '2013-01-27' => 46, '2014-01-26' => 49, '2016-04-10' => 47,
    '2017-01-22' => 49, '2018-01-21' => 50, '2019-01-27' => 55, '2021-08-29' => 58, '2022-07-10' => 60,
    '2023-01-22' => 63, '2023-07-09' => 66, '2024-01-21' => 68, '2024-07-14' => 73, '2025-07-13' => 78,
    '2026-07-12' => 82,
];

// US federal minimum wage in dollars an hour. Source: US Department of Labor, "History of Federal Minimum Wage Rates".
// The 1960s and 70s rates are the main rate for workers covered since 1938; some later-covered jobs had other rates.
const MINIMUM_WAGE = [
    '1939-10-24' => 0.30, '1945-10-24' => 0.40, '1950-01-25' => 0.75, '1956-03-01' => 1.00, '1961-09-03' => 1.15,
    '1963-09-03' => 1.25, '1967-02-01' => 1.40, '1968-02-01' => 1.60, '1974-05-01' => 2.00, '1975-01-01' => 2.10,
    '1976-01-01' => 2.30, '1978-01-01' => 2.65, '1979-01-01' => 2.90, '1980-01-01' => 3.10, '1981-01-01' => 3.35,
    '1990-04-01' => 3.80, '1991-04-01' => 4.25, '1996-10-01' => 4.75, '1997-09-01' => 5.15, '2007-07-24' => 5.85,
    '2008-07-24' => 6.55, '2009-07-24' => 7.25,
];

// The stamp price and the minimum wage on the birthday, and the same two today. Each is null if the table has
// nothing for that date.
function birth_prices(DateTimeImmutable $birthday, ?DateTimeImmutable $today = null): array
{
    $today ??= new DateTimeImmutable('today');

    return [
        'stamp_cents' => price_on(STAMP_CENTS, $birthday),
        'stamp_cents_now' => price_on(STAMP_CENTS, $today),
        'wage' => price_on(MINIMUM_WAGE, $birthday),
        'wage_now' => price_on(MINIMUM_WAGE, $today),
    ];
}

// The value of the last entry that started on or before the date.
function price_on(array $table, DateTimeImmutable $date): int|float|null
{
    $key = $date->format('Y-m-d');
    $value = null;

    foreach ($table as $from => $amount) {
        if ($from <= $key) {
            $value = $amount;
        }
    }

    return $value;
}
