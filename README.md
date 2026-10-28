# Born Here

Enter your name, birthday, and birth city, and get a scroll-driven page about the day you were born: the city and its weather at 12:00, the most notable events and people born that day, the top US chart song that week with a looping video, and a printable certificate.

Live at https://born-here.onrender.com

## Features

- **Input:** name, birthday (from 1 January 1940), city, and country. The country tells apart cities with the same name.
- **City and weather:** a Wikipedia summary and image for the city, plus the weather at 12:00 local time on the birth date.
- **Clock:** pinned to the corner. It maps your scroll position onto the day, from 00:00 at the top to 23:59 at the bottom.
- **Events:** the 12 most notable events for the date, shown oldest first. Times appear only when a source gives one.
- **People:** the 5 most notable people born on the date, with birthplace and what they're known for.
- **Music:** the Billboard Hot 100 number one in effect that week, with an embedded video that loops, fades out as you scroll away, and plays at 720p when available.
- **Certificate:** a printable card with the name, place, and date.

## Data sources

| Data | Source |
|---|---|
| City summary and image | Wikipedia |
| Events and people for the date | Wikipedia "On this day" feed, ranked with Wikidata |
| Weather | Open-Meteo historical archive and geocoding (no key needed) |
| Chart song | Billboard Hot 100 number ones, read from Wikipedia's year pages (1958 onward) |
| Music video | `data/videos.csv`, then the YouTube Data API if a key is set |

Results are cached in the database, so a repeat birthday loads instantly.

## Running locally

Requirements: PHP 8.4 with the `pdo_mysql` and `curl` extensions, and a MySQL or MariaDB server.

1. Create the database. Run `schema.sql` in your MySQL client, or import it in phpMyAdmin.
2. Start the server from this folder:

   ```bash
   php -S localhost:8000
   ```

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

A song with no entry falls back to the YouTube API if a key is set. Otherwise it shows a search link. Some videos block embedding on other sites, and the player then shows the reason.

## Hosting

- **Web app:** Render, as a Docker web service built from the `Dockerfile`, on the free plan. Set the environment variables above in Render's dashboard. Free services sleep after about 15 minutes without traffic, so the first visit after a break is slow.
- **Database:** TiDB Cloud Starter (MySQL-compatible). Set `DB_SSL=1`, and load `schema.sql` through its SQL editor.

## Limitations

- A birthday that isn't cached yet takes about 30 seconds to build.
- Event locations are missing for most events, because Wikidata rarely records them.
- The chart is the US Billboard Hot 100 only, and there's no chart data before 1958.
- Historical photos from the birth year aren't implemented. The city image is a current Wikipedia photo.
- Sourced controversies for the people aren't implemented yet.
- The music player has no pause control, since the player controls are hidden. The video pauses when the tab is hidden and fades out as you scroll away.
- The 720p request is a request, not a guarantee. YouTube may choose another quality.
- Sessions are stored on the server, so on the free Render plan a redeploy logs everyone out and they need to enter their details again.

## Project layout

- `index.php`: the input form
- `start.php`: validates the input and looks up the city
- `result.php`: the day page
- `includes/`: data sources (`wiki.php`, `billboard.php`, `weather.php`, `youtube.php`, `music.php`), caching (`app.php`), and the page builder (`day.php`)
- `assets/`: styles and scripts, including `music.js` for the player
- `data/videos.csv`: video IDs for chart songs
- `schema.sql`: the database schema
- `Dockerfile`: the container used for hosting
- `PLAN.md`: the original project plan
