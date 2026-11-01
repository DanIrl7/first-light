<?php
declare(strict_types=1);

// What everyday things cost in the United States on a birthday, next to today's prices: a stamp, an hour of work,
// a dozen eggs, a gallon of milk, a movie ticket, a gallon of gas and the median home. All fixed tables, so nothing is
// looked up. The stamp and wage tables start on an effective date and last until the next; the rest hold one figure a year.

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


// A dozen Grade A large eggs, in dollars. Source: US Bureau of Labor Statistics, average price series APU0000708111.
// Yearly averages of the monthly prices (2026 is the months so far). Nothing reliable goes back before 1980.
const EGGS_DOZEN = [
    1980 => 0.84, 1981 => 0.90, 1982 => 0.87, 1983 => 0.89, 1984 => 1.00, 1985 => 0.80, 1986 => 0.87,
    1987 => 0.78, 1988 => 0.79, 1989 => 1.00, 1990 => 1.01, 1991 => 0.99, 1992 => 0.86, 1993 => 0.91,
    1994 => 0.86, 1995 => 0.92, 1996 => 1.11, 1997 => 1.06, 1998 => 1.04, 1999 => 0.96, 2000 => 0.91,
    2001 => 0.93, 2002 => 1.03, 2003 => 1.24, 2004 => 1.34, 2005 => 1.22, 2006 => 1.31, 2007 => 1.68,
    2008 => 1.99, 2009 => 1.66, 2010 => 1.66, 2011 => 1.77, 2012 => 1.84, 2013 => 1.91, 2014 => 2.02,
    2015 => 2.47, 2016 => 1.68, 2017 => 1.47, 2018 => 1.74, 2019 => 1.40, 2020 => 1.51, 2021 => 1.67,
    2022 => 2.86, 2023 => 2.80, 2024 => 3.17, 2025 => 4.25, 2026 => 2.30,
];

// A gallon of whole milk, in dollars. Source: BLS APU0000709111 (half gallon, doubled) to 1994, APU0000709112 (gallon) after.
const MILK_GALLON = [
    1980 => 2.10, 1981 => 2.23, 1982 => 2.25, 1983 => 2.26, 1984 => 2.25, 1985 => 2.27, 1986 => 2.23,
    1987 => 2.27, 1988 => 2.33, 1989 => 2.54, 1990 => 2.85, 1991 => 2.74, 1992 => 2.78, 1993 => 2.79,
    1994 => 2.88, 1995 => 2.86, 1996 => 3.12, 1997 => 3.17, 1998 => 2.70, 1999 => 2.84, 2000 => 2.78,
    2001 => 2.88, 2002 => 2.76, 2003 => 2.76, 2004 => 3.16, 2005 => 3.19, 2006 => 3.08, 2007 => 3.50,
    2008 => 3.80, 2009 => 3.11, 2010 => 3.26, 2011 => 3.57, 2012 => 3.49, 2013 => 3.46, 2014 => 3.69,
    2015 => 3.42, 2016 => 3.20, 2017 => 3.23, 2018 => 2.90, 2019 => 3.04, 2020 => 3.32, 2021 => 3.54,
    2022 => 4.09, 2023 => 4.03, 2024 => 3.98, 2025 => 4.07, 2026 => 4.17,
];

// A gallon of regular gasoline, in dollars. Source: US Energy Information Administration, Monthly Energy Review table 9.4
// (leaded regular, yearly) to 1975, then BLS APU000074714 (unleaded regular) from 1976. Nothing goes back before 1949.
const GAS_GALLON = [
    1949 => 0.268, 1950 => 0.268, 1951 => 0.272, 1952 => 0.274, 1953 => 0.287, 1954 => 0.290, 1955 => 0.291,
    1956 => 0.299, 1957 => 0.310, 1958 => 0.304, 1959 => 0.305, 1960 => 0.311, 1961 => 0.308, 1962 => 0.306,
    1963 => 0.304, 1964 => 0.304, 1965 => 0.312, 1966 => 0.321, 1967 => 0.332, 1968 => 0.337, 1969 => 0.348,
    1970 => 0.357, 1971 => 0.364, 1972 => 0.361, 1973 => 0.388, 1974 => 0.532, 1975 => 0.567, 1976 => 0.610,
    1977 => 0.660, 1978 => 0.670, 1979 => 0.900, 1980 => 1.250, 1981 => 1.380, 1982 => 1.300, 1983 => 1.240,
    1984 => 1.210, 1985 => 1.200, 1986 => 0.930, 1987 => 0.950, 1988 => 0.950, 1989 => 1.020, 1990 => 1.160,
    1991 => 1.140, 1992 => 1.130, 1993 => 1.110, 1994 => 1.110, 1995 => 1.150, 1996 => 1.230, 1997 => 1.230,
    1998 => 1.060, 1999 => 1.170, 2000 => 1.510, 2001 => 1.460, 2002 => 1.360, 2003 => 1.590, 2004 => 1.880,
    2005 => 2.300, 2006 => 2.590, 2007 => 2.800, 2008 => 3.270, 2009 => 2.350, 2010 => 2.790, 2011 => 3.530,
    2012 => 3.640, 2013 => 3.530, 2014 => 3.370, 2015 => 2.450, 2016 => 2.140, 2017 => 2.410, 2018 => 2.740,
    2019 => 2.640, 2020 => 2.170, 2021 => 3.050, 2022 => 4.090, 2023 => 3.660, 2024 => 3.450, 2025 => 3.260,
    2026 => 3.970,
];

// Median sales price of new houses sold in the US, in dollars, from 1963. Source: US Census Bureau and HUD, via FRED MSPUS.
// Yearly averages of the quarterly figures (2026 is the quarters so far).
const NEW_HOME_PRICE = [
    1963 => 18050, 1964 => 18925, 1965 => 20125, 1966 => 21500, 1967 => 22750, 1968 => 24800, 1969 => 25600,
    1970 => 23475, 1971 => 25225, 1972 => 27525, 1973 => 32600, 1974 => 36050, 1975 => 39275, 1976 => 44225,
    1977 => 48900, 1978 => 55850, 1979 => 62750, 1980 => 64750, 1981 => 68950, 1982 => 69225, 1983 => 75375,
    1984 => 79950, 1985 => 84275, 1986 => 92025, 1987 => 104700, 1988 => 112225, 1989 => 120425, 1990 => 122300,
    1991 => 119975, 1992 => 121375, 1993 => 126500, 1994 => 130425, 1995 => 133475, 1996 => 140250, 1997 => 145000,
    1998 => 151925, 1999 => 160125, 2000 => 167550, 2001 => 173100, 2002 => 186025, 2003 => 192125, 2004 => 218150,
    2005 => 236550, 2006 => 243750, 2007 => 244950, 2008 => 229550, 2009 => 215650, 2010 => 222700, 2011 => 224900,
    2012 => 244400, 2013 => 266225, 2014 => 285775, 2015 => 294150, 2016 => 305125, 2017 => 322425, 2018 => 325275,
    2019 => 320250, 2020 => 328150, 2021 => 383000, 2022 => 432950, 2023 => 426525, 2024 => 418975, 2025 => 415400,
    2026 => 405100,
];

// Average US movie ticket, in dollars. Source: National Association of Theatre Owners figures, as compiled by Cheapism and
// Box Office Mojo. NATO did not collect every year, so some years from 1954 to 1973 are estimated between the years it did.
// Nothing goes back before 1954, and this table stops in 2017.
const MOVIE_TICKET = [
    1954 => 0.49, 1955 => 0.54, 1956 => 0.59, 1957 => 0.63, 1958 => 0.68, 1959 => 0.72, 1960 => 0.75,
    1961 => 0.79, 1962 => 0.82, 1963 => 0.86, 1964 => 0.95, 1965 => 1.04, 1966 => 1.13, 1967 => 1.22,
    1968 => 1.33, 1969 => 1.44, 1970 => 1.54, 1971 => 1.65, 1972 => 1.73, 1973 => 1.81, 1974 => 1.89,
    1975 => 2.03, 1976 => 2.13, 1977 => 2.23, 1978 => 2.34, 1979 => 2.47, 1980 => 2.69, 1981 => 2.78,
    1982 => 2.94, 1983 => 3.15, 1984 => 3.36, 1985 => 3.55, 1986 => 3.71, 1987 => 3.91, 1988 => 4.11,
    1989 => 3.99, 1990 => 4.22, 1991 => 4.21, 1992 => 4.15, 1993 => 4.14, 1994 => 4.08, 1995 => 4.35,
    1996 => 4.42, 1997 => 4.59, 1998 => 4.69, 1999 => 5.06, 2000 => 5.39, 2001 => 5.66, 2002 => 5.81,
    2003 => 6.03, 2004 => 6.21, 2005 => 6.41, 2006 => 6.55, 2007 => 6.88, 2008 => 7.18, 2009 => 7.50,
    2010 => 7.89, 2011 => 7.93, 2012 => 7.96, 2013 => 8.13, 2014 => 8.17, 2015 => 8.12, 2016 => 8.65,
    2017 => 8.93,
];

// Median value of an owner-occupied home from the decennial Census, in dollars. Source: US Census Bureau, "Median Home
// Values: Unadjusted" (historical census of housing tables). It is used for 1940 to 1962, each year taking the
// most recent census, because the new-home series only starts in 1963.
const CENSUS_HOME_VALUE = [1940 => 2938, 1950 => 7354, 1960 => 11900];

// What is on the receipt, in order: key, the name printed on it, and the table of yearly prices.
const RECEIPT_ITEMS = [
    ['eggs', '12 eggs', EGGS_DOZEN],
    ['milk', 'Gallon of milk', MILK_GALLON],
    ['ticket', 'Movie ticket', MOVIE_TICKET],
    ['gas', 'Gallon of gas', GAS_GALLON],
];

// "Today" for each item is the newest figure we hold, as [year, price]: September 2026 for eggs, milk and gas, the third
// quarter of 2026 for the home, and 2017 for the movie ticket, whose table stops there. The year is kept so the page can
// say when a figure is older than this year. Update these when the tables are refreshed.
const NOW_PRICES = [
    'eggs' => [2026, 2.27],
    'milk' => [2026, 4.11],
    'gas' => [2026, 4.48],
    'ticket' => [2017, 8.93],
    'home' => [2026, 393300],
];

// The receipt for a birthday: one row per item with the price that year, today's price, and the year today's figure is
// from. A price is null when the table has nothing for that year (eggs and milk before 1980, tickets before 1954 or
// after 2017, gas before 1949).
function receipt_rows(DateTimeImmutable $birthday): array
{
    $year = (int) $birthday->format('Y');
    $rows = [];

    foreach (RECEIPT_ITEMS as [$key, $label, $table]) {
        [$nowYear, $now] = NOW_PRICES[$key];
        $rows[] = [
            'key' => $key,
            'label' => $label,
            'then' => $table[$year] ?? null,
            'now' => $now,
            'now_year' => $nowYear,
        ];
    }

    return $rows;
}

// The median home price in the birth year: ['value', 'now', 'kind'], or null before 1940. 'kind' says what the figure
// measures: "census" is the median value of homes people owned, "new" is the median price new houses sold for.
function home_price(DateTimeImmutable $birthday): ?array
{
    $year = (int) $birthday->format('Y');
    [$nowYear, $now] = NOW_PRICES['home'];

    if (isset(NEW_HOME_PRICE[$year])) {
        return ['value' => NEW_HOME_PRICE[$year], 'now' => $now, 'now_year' => $nowYear, 'kind' => 'new', 'census_year' => null];
    }

    foreach (array_reverse(CENSUS_HOME_VALUE, true) as $censusYear => $value) {
        if ($censusYear <= $year) {
            return ['value' => $value, 'now' => $now, 'now_year' => $nowYear, 'kind' => 'census', 'census_year' => $censusYear];
        }
    }

    return null;
}

// Every price for a birthday: the stamp and minimum wage (next to today's), the receipt rows and the median home.
// Each is null if the table has nothing for that date.
function birth_prices(DateTimeImmutable $birthday, ?DateTimeImmutable $today = null): array
{
    $today ??= new DateTimeImmutable('today');

    return [
        'stamp_cents' => price_on(STAMP_CENTS, $birthday),
        'stamp_cents_now' => price_on(STAMP_CENTS, $today),
        'wage' => price_on(MINIMUM_WAGE, $birthday),
        'wage_now' => price_on(MINIMUM_WAGE, $today),
        'receipt' => receipt_rows($birthday),
        'home' => home_price($birthday),
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
