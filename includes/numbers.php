<?php
declare(strict_types=1);

// Emoji for the readout cards, by sign, animal and moon phase name.
const NUMBER_EMOJI = [
    'star' => [
        'Aquarius' => '♒', 'Pisces' => '♓', 'Aries' => '♈', 'Taurus' => '♉', 'Gemini' => '♊', 'Cancer' => '♋',
        'Leo' => '♌', 'Virgo' => '♍', 'Libra' => '♎', 'Scorpio' => '♏', 'Sagittarius' => '♐', 'Capricorn' => '♑',
    ],
    'animal' => [
        'Rat' => '🐀', 'Ox' => '🐂', 'Tiger' => '🐅', 'Rabbit' => '🐇', 'Dragon' => '🐉', 'Snake' => '🐍',
        'Horse' => '🐎', 'Goat' => '🐐', 'Monkey' => '🐒', 'Rooster' => '🐓', 'Dog' => '🐕', 'Pig' => '🐖',
    ],
    'moon' => [
        'New moon' => '🌑', 'Waxing crescent' => '🌒', 'First quarter' => '🌓', 'Waxing gibbous' => '🌔',
        'Full moon' => '🌕', 'Waning gibbous' => '🌖', 'Last quarter' => '🌗', 'Waning crescent' => '🌘',
    ],
];

// Facts about a birthday that need no lookup: the weekday, star sign, Chinese zodiac animal, moon phase,
// and days alive. Everything here is plain arithmetic, so nothing is cached.
function day_numbers(DateTimeImmutable $birthday, ?DateTimeImmutable $today = null): array
{
    $today ??= new DateTimeImmutable('today');
    $alive = (int) $birthday->diff($today)->format('%r%a');

    return [
        'weekday' => $birthday->format('l'),
        'star_sign' => star_sign((int) $birthday->format('n'), (int) $birthday->format('j')),
        'chinese_zodiac' => chinese_zodiac($birthday),
        'moon' => moon_phase($birthday),
        'days_alive' => max(0, $alive),
    ];
}

// Western zodiac sign for a month and day. Each sign starts on the listed date.
function star_sign(int $month, int $day): string
{
    $starts = [
        [1, 20, 'Aquarius'], [2, 19, 'Pisces'], [3, 21, 'Aries'], [4, 20, 'Taurus'],
        [5, 21, 'Gemini'], [6, 21, 'Cancer'], [7, 23, 'Leo'], [8, 23, 'Virgo'],
        [9, 23, 'Libra'], [10, 23, 'Scorpio'], [11, 22, 'Sagittarius'], [12, 22, 'Capricorn'],
    ];

    $sign = 'Capricorn'; // 1 January up to the 19th

    foreach ($starts as [$startMonth, $startDay, $name]) {
        if ($month > $startMonth || ($month === $startMonth && $day >= $startDay)) {
            $sign = $name;
        }
    }

    return $sign;
}

// The Chinese zodiac animal starts at the Lunar New Year, not on 1 January, so January and February
// birthdays can belong to the previous animal. These are the Lunar New Year dates (month-day) per year.
const LUNAR_NEW_YEAR = [
    1940 => '02-08', 1941 => '01-27', 1942 => '02-15', 1943 => '02-05', 1944 => '01-25',
    1945 => '02-13', 1946 => '02-02', 1947 => '01-22', 1948 => '02-10', 1949 => '01-29',
    1950 => '02-17', 1951 => '02-06', 1952 => '01-27', 1953 => '02-14', 1954 => '02-03',
    1955 => '01-24', 1956 => '02-12', 1957 => '01-31', 1958 => '02-18', 1959 => '02-08',
    1960 => '01-28', 1961 => '02-15', 1962 => '02-05', 1963 => '01-25', 1964 => '02-13',
    1965 => '02-02', 1966 => '01-21', 1967 => '02-09', 1968 => '01-30', 1969 => '02-17',
    1970 => '02-06', 1971 => '01-27', 1972 => '02-15', 1973 => '02-03', 1974 => '01-23',
    1975 => '02-11', 1976 => '01-31', 1977 => '02-18', 1978 => '02-07', 1979 => '01-28',
    1980 => '02-16', 1981 => '02-05', 1982 => '01-25', 1983 => '02-13', 1984 => '02-02',
    1985 => '02-20', 1986 => '02-09', 1987 => '01-29', 1988 => '02-17', 1989 => '02-06',
    1990 => '01-27', 1991 => '02-15', 1992 => '02-04', 1993 => '01-23', 1994 => '02-10',
    1995 => '01-31', 1996 => '02-19', 1997 => '02-07', 1998 => '01-28', 1999 => '02-16',
    2000 => '02-05', 2001 => '01-24', 2002 => '02-12', 2003 => '02-01', 2004 => '01-22',
    2005 => '02-09', 2006 => '01-29', 2007 => '02-18', 2008 => '02-07', 2009 => '01-26',
    2010 => '02-14', 2011 => '02-03', 2012 => '01-23', 2013 => '02-10', 2014 => '01-31',
    2015 => '02-19', 2016 => '02-08', 2017 => '01-28', 2018 => '02-16', 2019 => '02-05',
    2020 => '01-25', 2021 => '02-12', 2022 => '02-01', 2023 => '01-22', 2024 => '02-10',
    2025 => '01-29', 2026 => '02-17', 2027 => '02-06', 2028 => '01-26', 2029 => '02-13',
    2030 => '02-03',
];

function chinese_zodiac(DateTimeImmutable $date): string
{
    $animals = ['Rat', 'Ox', 'Tiger', 'Rabbit', 'Dragon', 'Snake', 'Horse', 'Goat', 'Monkey', 'Rooster', 'Dog', 'Pig'];
    $year = (int) $date->format('Y');
    $newYear = LUNAR_NEW_YEAR[$year] ?? null;

    // Before the Lunar New Year, the person still belongs to the previous year's animal.
    if ($newYear !== null && $date->format('m-d') < $newYear) {
        $year--;
    }

    // 1996 was a year of the Rat, and the cycle repeats every 12 years.
    return $animals[(($year - 1996) % 12 + 12) % 12];
}

// The moon's phase at noon UTC on the date, counted from a known new moon (6 January 2000, 18:14 UTC).
// Accurate to within about a day, which is as close as a phase name needs to be.
function moon_phase(DateTimeImmutable $date): array
{
    $synodicMonth = 29.530588853;
    $knownNewMoon = gmmktime(18, 14, 0, 1, 6, 2000);
    $noon = gmmktime(12, 0, 0, (int) $date->format('n'), (int) $date->format('j'), (int) $date->format('Y'));

    $age = fmod(($noon - $knownNewMoon) / 86400, $synodicMonth);

    if ($age < 0) {
        $age += $synodicMonth;
    }

    $names = ['New moon', 'Waxing crescent', 'First quarter', 'Waxing gibbous', 'Full moon', 'Waning gibbous', 'Last quarter', 'Waning crescent'];
    $name = $names[(int) floor($age / $synodicMonth * 8 + 0.5) % 8];

    return [
        'name' => $name,
        'lit' => (int) round((1 - cos(2 * M_PI * $age / $synodicMonth)) / 2 * 100),
    ];
}
