# Born Here: project plan

A scroll-driven PHP site that takes a person's name, birthday, and birth city, then tells the story of the world on the day they were born.

## 1. Input

Three fields, submitted by POST and kept in the session. Never put them in the URL.

- **Name**
- **Birthday** (day, month, year)
- **Birth city and country.** The country resolves ambiguous city names. Use a geocoding lookup to get coordinates and the local timezone.

## 2. The scroll experience

The page opens on the birth city at **12:00** on the birth date.

1. **Intro.** A short description of the city (Wikipedia summary), an era photo from Wikimedia Commons, and the weather at 12:00 that day. If no era photo exists, show a generic photo of the city.
2. **Clock.** A fixed clock in the corner shows the time of the content currently on screen. Items with a known time move the clock to that time. Items without one show only the date.
3. **Events (12).** Each event shows its photo, date, place, and a short description. The page states that the list is curated by Wikipedia editors.
4. **People (5).** Each person shows a photo, birthplace, what they're known for, and any major controversies with a source. No moral labels.
5. **Music.** A looping official music video in its own section, with the top song from the birth week. Details below.
6. **Certificate.** A closing card with the name and birth details, a share image, and a download button.

## 3. Music

- **Which chart.** No single global chart exists for most birth years. Use the **Billboard Hot 100** (US), which has weekly data back to 1958, and say on the page that it's a US chart. Offer a country selector later.
- **Playback.** Embed the official video with the **YouTube IFrame API**. Do not download or host audio files. Show a "Play music" button, because browsers block sound until the user interacts with the page.
- **Loop.** Use the player's loop setting. It needs the same video ID in the playlist parameter.
- **Volume on scroll.** Set the volume from the section's distance off-screen. Full volume inside the section, fading to 0 across about one screen height, using `setVolume()`.
- **Pause.** Pause the video when the tab is hidden.
- **Fallback.** If there's no chart entry or no embeddable video, hide the section.

## 4. Data sources

| Content | Source | Notes |
|---|---|---|
| Famous people, birthplaces, photos | Wikidata (SPARQL) | Free license. Birth times are rarely known. |
| Events on a date | Wikipedia "On this day" feed | Curated. Most entries have a date but no time. |
| Weather at birth | Open-Meteo historical archive | Hourly, back to 1940, no key. Use the 12:00 value. |
| Top song of birth week | Billboard Hot 100 weekly data | US chart. Confirm the source before building. |
| Photos | Wikimedia Commons | Store the license and author for attribution. |
| Moon phase, sunrise, sunset | Calculated in PHP | No API needed. |

Wikimedia requires a descriptive `User-Agent` header on every request.

## 5. Caching (MySQL)

- **People and events:** cache per month and day. Many people share a birthday, so this keeps the API calls low.
- **Weather:** cache per city and date.
- **Chart entries:** cache per week.
- **Images:** store the URL, license, and author.

Cache only public data. Don't store names or birthdays.

## 6. Privacy, safety, and licensing

- **Personal data.** Keep name and birthday in the session only. Don't log them.
- **Attribution.** Show credits for Wikipedia (CC BY-SA), Wikidata (CC0), Commons images (license per file), Open-Meteo, and the chart source.
- **Video.** Use only the official YouTube embed, with no downloads.
- **Sensitive content.** Some events involve tragedies. Show a content warning and let users hide those events.
- **Accuracy.** Every claim comes from a source link, and the page says when a time or place is unknown.

## 7. Build order

1. Input form and geocoding (city and country to coordinates and timezone).
2. Data layer: Wikidata, On-this-day, Open-Meteo, chart, and the MySQL cache.
3. Scroll layout and the corner clock, using GSAP ScrollTrigger from your portfolio.
4. Music section with the volume fade.
5. Certificate image (GD) and sharing.
6. Polish: mobile layout, reduced-motion support, accessibility, and attribution.

## 8. Risks

- **Image coverage.** Small places may have no era photos, so the fallback will be common.
- **"Most famous" is subjective.** The events list reflects the Wikipedia editors' choices.
- **Chart coverage.** Billboard only covers the US, and not every year has an entry.
- **Video availability.** Embedded videos can be removed or blocked in some regions, so the fallback needs to work.
- **Rate limits.** Caching is the main protection.

## 9. Decisions

Decided:
1. **Chart:** US Billboard Hot 100.
2. **Clock:** starts at midnight (12:00 AM) on the birth date and moves through the day as you scroll.
3. **Music start:** click-to-play, not muted autoplay.
4. **Certificate:** included in the first version.
