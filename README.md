# First Light

Enter your name, birthday, and birth place, and get a scroll-driven, cinematic page about the day you were born: the city and its weather at 12:00, who was in charge of the country, the most notable events and people born that day, and the top US chart song that week, playing all the way down the page.

Live at https://born-here.onrender.com (the address comes from the Render service name, which still carries the old project name).

## Features

- **Input:** name, birthday (from 1 January 1940), and birth place. The place is picked from a suggestion list, so cities with the same name are told apart. There's a visible calendar for the birthday, and a fallback city and country field if the list isn't available. If a submit fails, every field keeps what was typed and each error shows under its field.
- **Surprise me!:** a button on the form fills in a random date (from 1958, so there's always a chart song) and a random well-known city. The visitor only adds a name. There is another at the bottom of the day page, which keeps the name and starts a new random day.
- **Loading screen:** the first time a day is built, a screen shows each step (city, leaders, weather, song, growing-up songs, cinemas and record shops, people, events, also on this day, video) as it finishes, with a retry if something fails.
- **Open the hatch:** the day page opens behind a cover while the music video loads. The button unlocks once the video is playing. If that takes more than 30 seconds, or the video can't load, "Step out without sound" appears. The click is what lets the browser play sound.
- **Music:** the Billboard Hot 100 number one in effect that week. It plays all the way down the page, a little quieter away from its own section, and a button in the corner turns it on and off. The video loops and requests 720p.
- **City and weather:** a Wikipedia summary and image for the city, plus the weather at 12:00 local time on the birth date.
- **Weather details:** besides the noon reading, the day's high and low, rain, top wind speed, and sunrise and sunset times. The sky behind the page turns at the real sunrise and sunset of the birth place, and the sun crosses the sky between them.
- **Your day in numbers:** the weekday, star sign, Chinese zodiac animal (by the Lunar New Year, not 1 January), the moon's phase that night, days alive, and the date of your 10,000th day.
- **Prices:** in the numbers section, the US first-class stamp and the US federal minimum wage on your birthday, next to today's. These come from fixed tables (USPS and the Department of Labor), so they are US-only and cover 1940 onward.
- **Cinemas and record shops:** the number one film at the US box office that weekend, and the number one album on the Billboard 200 that week. Each shows a short message if Wikipedia has no list for that date.
- **Also on this day:** well-known people who died on the date, and holidays and observances, from Wikipedia's "On this day" feed.
- **Growing up on the charts:** the Billboard number one on your 13th, 16th, 18th and 21st birthdays. Birthdays that haven't happened yet, or fall before 1958, are skipped.
- **Leaders:** the head of state and head of government of the birth country on that day.
- **Clock and sky:** a clock pinned to the corner maps your scroll position onto the day, from 00:00 at the top to 23:59 at the bottom. The sky behind the page follows it: night, dawn, noon, dusk, and night again, with a sun that crosses the screen.
- **Events:** the 12 most notable events for the date, shown oldest first, with photos when Wikipedia has them. Times appear only when a source gives one.
- **People:** the 5 most notable people born on the date, with birthplace and what they're known for, in a pinned strip that scrolls sideways.
- **Certificate:** a card with the name, place, and date.
- **Motion:** scrolling is smoothed with GSAP ScrollSmoother, and sections animate in with ScrollTrigger. The animations always run, even when the visitor's system asks for reduced motion.

## Data sources

| Data | Source |
|---|---|
| City summary and image | Wikipedia |
| Events and people for the date | Wikipedia "On this day" feed, ranked with Wikidata |
| Deaths and holidays for the date | Wikipedia "On this day" feed (deaths ranked with Wikidata) |
| Country leaders | Wikidata |
| Place search | Open-Meteo geocoding (no key needed) |
| Weather, sunrise and sunset | Open-Meteo historical archive (no key needed) |
| Number one film and album | Wikipedia's yearly lists of US box office number-one films and Billboard 200 number-one albums |
| Stamp price, minimum wage | Fixed tables in `includes/prices.php`, from the USPS and the US Department of Labor |
| Star sign, zodiac, moon phase, day counts | Calculated in `includes/numbers.php` (Lunar New Year dates are a built-in table for 1940–2030) |
| Chart song | Billboard Hot 100 number ones, read from Wikipedia's year pages (1958 onward) |
| Music video | `data/videos.csv`, then the YouTube Data API if a key is set |

Results are cached in the database, so a repeat birthday loads instantly.

## Design

- **Palette:** deep indigo background, mint accent, cool white text. The sky layers shift through rose-violet, blue, and plum. The colours are CSS variables at the top of `assets/style.css`.
- **Fonts:** Fraunces for headings, Manrope for body text, and DM Mono for small labels, loaded from Google Fonts.
- **Animation:** [GSAP](https://gsap.com) 3.13 (ScrollTrigger and ScrollSmoother), loaded from jsDelivr.

## Running locally

Requirements: PHP 8.4 with the `pdo_mysql` and `curl` extensions, and a MySQL or MariaDB server.

1. Create the database. Run `schema.sql` in your MySQL client, or import it in phpMyAdmin.
2. Start the server from this folder:

   ```bash
   php -S localhost:8000 -t . scripts/router.php
   ```

   The router gives the built-in server the same clean URLs as the live site (`/result` instead of `/result.php`).

3. Open http://localhost:8000.

With no environment variables set, the app connects to `127.0.0.1` as `root` with an empty password, which is fine for local use only.

## Configuration

The app reads these environment variables. Anything not set falls back to the local defaults in `config.php`.

| Variable | Purpose |
|---|---|
| `DB_HOST` | Database host |
| `DB_PORT` | Database port (default 3306) |
| `DB_NAME` | Database name (`born_here`) |
| `DB_USER` | Database user |
| `DB_PASS` | Database password. Keep it secret. |
| `DB_SSL` | Set to `1` for hosted databases that require TLS |
| `YOUTUBE_API_KEY` | Optional. Lets the app look up embeddable videos for songs not in `data/videos.csv`. Keep it secret. |

## Adding music videos

`data/videos.csv` has three columns: artist, song, and YouTube video ID. Match the artist and song exactly as the chart lists them, ignoring case.

```csv
artist,song,youtube_id
Rihanna featuring Calvin Harris,We Found Love,tg00YEETFzg
```

A song with no entry falls back to the YouTube API if a key is set. Otherwise the song is shown with a search link and there's no click-to-begin cover. Some videos block embedding on other sites, and the cover then says why and lets the visitor continue.

### Filling in the list in bulk

`scripts/build_videos.php` looks up a video for every Billboard number one and appends the embeddable ones to the CSV:

```bash
YOUTUBE_API_KEY=your-key php scripts/build_videos.php --limit=90
```

Each new search costs 100 units of YouTube's 10,000-unit daily quota, so `--limit` caps the searches per run. Songs already listed or cached are skipped, so running it again the next day continues where it stopped. Never commit the key.

## Hosting

- **Web app:** Render, as a Docker web service built from the `Dockerfile` (Apache with `mod_rewrite`), on the free plan. Set the environment variables above in Render's dashboard. Free services sleep after about 15 minutes without traffic, so the first visit after a break is slow.
- **Database:** TiDB Cloud Starter (MySQL-compatible). Set `DB_SSL=1`, and load `schema.sql` through its SQL editor.
- **Clean URLs:** `docker/clean-urls.conf` serves `/result` from `result.php` and redirects `.php` addresses to the clean ones.

## Limitations

- A birthday that isn't cached yet takes about 30 seconds to build.
- Event locations are missing for most events, because Wikidata rarely records them.
- The chart is the US Billboard Hot 100 only, and there's no chart data before 1958.
- Historical photos from the birth year aren't implemented. The city image is a current Wikipedia photo.
- Sourced controversies for the people aren't implemented yet.
- Sound needs the visitor's click, because browsers block it otherwise. Visitors who continue without sound can turn it on later from the corner button.
- The 720p request is a request, not a guarantee. YouTube may choose another quality.
- Smooth scrolling is off on touch screens, which keep their native scrolling.
- Sessions are stored on the server, so on the free Render plan a redeploy logs everyone out and they need to enter their details again.

## Project layout

- `index.php`: the input form
- `start.php`: validates the input and looks up the city
- `loading.php`: the loading screen
- `build.php`: gathers the day and reports progress to the loading screen
- `result.php`: the day page
- `includes/`: data sources (`wiki.php`, `billboard.php`, `weather.php`, `geocode.php`, `leaders.php`, `numbers.php`, `culture.php`, `prices.php`, `youtube.php`, `music.php`), caching (`app.php`), and the page builder (`day.php`)
- `assets/`: styles and scripts. `cinema.js` holds the scroll animations, `gate.js` the click-to-begin cover, `music.js` the player, `day.js` the clock and `random.js` the randomize button
- `data/videos.csv`: video IDs for chart songs
- `schema.sql`: the database schema
- `Dockerfile`: the container used for hosting
- `docker/clean-urls.conf`: Apache rules for clean URLs on the live site
- `scripts/router.php`: the same clean URLs for `php -S` locally
- `scripts/build_videos.php`: bulk video lookup for the chart songs
- `PLAN.md`: the original project plan
