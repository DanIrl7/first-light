// "Randomize the day": a random date and a well-known city, so someone can see what the page does without typing.
// On the start form the button fills in the birthday and birth place and leaves the name for the visitor.
// At the bottom of the day page it sends the same name straight through for a new random day.
(function () {
    const button = document.getElementById('random-day');
    const again = document.getElementById('random-again');
    const birthday = document.getElementById('birthday-native');
    const search = document.getElementById('place-search');
    const nameInput = document.getElementById('field-name');
    const note = document.getElementById('random-note');

    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];

    // The Billboard chart starts in 1958, so a random day from then on always has a song.
    const EARLIEST = '1958-01-01';

    // name, region, country, country code, latitude, longitude, timezone
    const PLACES = [
        ['London', 'England', 'United Kingdom', 'GB', 51.5074, -0.1278, 'Europe/London'],
        ['Paris', 'Île-de-France', 'France', 'FR', 48.8566, 2.3522, 'Europe/Paris'],
        ['Berlin', 'Berlin', 'Germany', 'DE', 52.52, 13.405, 'Europe/Berlin'],
        ['Madrid', 'Community of Madrid', 'Spain', 'ES', 40.4168, -3.7038, 'Europe/Madrid'],
        ['Rome', 'Lazio', 'Italy', 'IT', 41.9028, 12.4964, 'Europe/Rome'],
        ['Lisbon', 'Lisbon', 'Portugal', 'PT', 38.7223, -9.1393, 'Europe/Lisbon'],
        ['Dublin', 'Leinster', 'Ireland', 'IE', 53.3498, -6.2603, 'Europe/Dublin'],
        ['Stockholm', 'Stockholm', 'Sweden', 'SE', 59.3293, 18.0686, 'Europe/Stockholm'],
        ['Athens', 'Attica', 'Greece', 'GR', 37.9838, 23.7275, 'Europe/Athens'],
        ['Istanbul', 'Istanbul', 'Turkey', 'TR', 41.0082, 28.9784, 'Europe/Istanbul'],
        ['Cairo', 'Cairo', 'Egypt', 'EG', 30.0444, 31.2357, 'Africa/Cairo'],
        ['Lagos', 'Lagos', 'Nigeria', 'NG', 6.5244, 3.3792, 'Africa/Lagos'],
        ['Nairobi', 'Nairobi', 'Kenya', 'KE', -1.2921, 36.8219, 'Africa/Nairobi'],
        ['Cape Town', 'Western Cape', 'South Africa', 'ZA', -33.9249, 18.4241, 'Africa/Johannesburg'],
        ['Johannesburg', 'Gauteng', 'South Africa', 'ZA', -26.2041, 28.0473, 'Africa/Johannesburg'],
        ['Durban', 'KwaZulu-Natal', 'South Africa', 'ZA', -29.8587, 31.0218, 'Africa/Johannesburg'],
        ['Accra', 'Greater Accra', 'Ghana', 'GH', 5.6037, -0.187, 'Africa/Accra'],
        ['Casablanca', 'Casablanca-Settat', 'Morocco', 'MA', 33.5731, -7.5898, 'Africa/Casablanca'],
        ['Mumbai', 'Maharashtra', 'India', 'IN', 19.076, 72.8777, 'Asia/Kolkata'],
        ['Delhi', 'Delhi', 'India', 'IN', 28.7041, 77.1025, 'Asia/Kolkata'],
        ['Dubai', 'Dubai', 'United Arab Emirates', 'AE', 25.2048, 55.2708, 'Asia/Dubai'],
        ['Bangkok', 'Bangkok', 'Thailand', 'TH', 13.7563, 100.5018, 'Asia/Bangkok'],
        ['Singapore', '', 'Singapore', 'SG', 1.3521, 103.8198, 'Asia/Singapore'],
        ['Hong Kong', '', 'Hong Kong', 'HK', 22.3193, 114.1694, 'Asia/Hong_Kong'],
        ['Tokyo', 'Tokyo', 'Japan', 'JP', 35.6762, 139.6503, 'Asia/Tokyo'],
        ['Seoul', 'Seoul', 'South Korea', 'KR', 37.5665, 126.978, 'Asia/Seoul'],
        ['Shanghai', 'Shanghai', 'China', 'CN', 31.2304, 121.4737, 'Asia/Shanghai'],
        ['Manila', 'Metro Manila', 'Philippines', 'PH', 14.5995, 120.9842, 'Asia/Manila'],
        ['Jakarta', 'Jakarta', 'Indonesia', 'ID', -6.2088, 106.8456, 'Asia/Jakarta'],
        ['Sydney', 'New South Wales', 'Australia', 'AU', -33.8688, 151.2093, 'Australia/Sydney'],
        ['Melbourne', 'Victoria', 'Australia', 'AU', -37.8136, 144.9631, 'Australia/Melbourne'],
        ['Auckland', 'Auckland', 'New Zealand', 'NZ', -36.8485, 174.7633, 'Pacific/Auckland'],
        ['New York', 'New York', 'United States', 'US', 40.7128, -74.006, 'America/New_York'],
        ['Chicago', 'Illinois', 'United States', 'US', 41.8781, -87.6298, 'America/Chicago'],
        ['Los Angeles', 'California', 'United States', 'US', 34.0522, -118.2437, 'America/Los_Angeles'],
        ['Houston', 'Texas', 'United States', 'US', 29.7604, -95.3698, 'America/Chicago'],
        ['Toronto', 'Ontario', 'Canada', 'CA', 43.6532, -79.3832, 'America/Toronto'],
        ['Vancouver', 'British Columbia', 'Canada', 'CA', 49.2827, -123.1207, 'America/Vancouver'],
        ['Mexico City', 'Mexico City', 'Mexico', 'MX', 19.4326, -99.1332, 'America/Mexico_City'],
        ['Havana', 'La Habana', 'Cuba', 'CU', 23.1136, -82.3666, 'America/Havana'],
        ['Bogotá', 'Bogotá', 'Colombia', 'CO', 4.711, -74.0721, 'America/Bogota'],
        ['Lima', 'Lima', 'Peru', 'PE', -12.0464, -77.0428, 'America/Lima'],
        ['São Paulo', 'São Paulo', 'Brazil', 'BR', -23.5505, -46.6333, 'America/Sao_Paulo'],
        ['Rio de Janeiro', 'Rio de Janeiro', 'Brazil', 'BR', -22.9068, -43.1729, 'America/Sao_Paulo'],
        ['Buenos Aires', 'Buenos Aires', 'Argentina', 'AR', -34.6037, -58.3816, 'America/Argentina/Buenos_Aires'],
        ['Santiago', 'Santiago Metropolitan', 'Chile', 'CL', -33.4489, -70.6693, 'America/Santiago'],
    ];

    function pick(list) {
        return list[Math.floor(Math.random() * list.length)];
    }

    function todayIso() {
        const now = new Date();

        return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    }

    function utc(iso) {
        return Date.UTC(...iso.split('-').map((n, i) => (i === 1 ? Number(n) - 1 : Number(n))));
    }

    function randomDate() {
        const first = birthday && birthday.min > EARLIEST ? birthday.min : EARLIEST;
        const start = utc(first);
        const end = utc(birthday ? birthday.max : todayIso());
        const day = new Date(start + Math.floor(Math.random() * ((end - start) / 86400000 + 1)) * 86400000);

        return {
            iso: day.toISOString().slice(0, 10),
            text: `${day.getUTCDate()} ${MONTHS[day.getUTCMonth()]} ${day.getUTCFullYear()}`,
        };
    }

    // One random choice: a date and a place, with the place's text as the form shows it.
    function randomChoice() {
        const date = randomDate();
        const place = pick(PLACES);
        const [name, region, country] = place;
        const label = [name, region, country].filter((part, k, all) => part && all.indexOf(part) === k).join(', ');

        return { date, place, label };
    }

    // Puts a choice into a form's fields, found by name.
    function fillFields(form, { date, place }) {
        const [name, region, country, code, latitude, longitude, timezone] = place;
        const values = {
            birthday: date.iso,
            place_name: name,
            place_region: region,
            place_country: country,
            place_country_code: code,
            place_latitude: latitude,
            place_longitude: longitude,
            place_timezone: timezone,
        };

        Object.entries(values).forEach(([field, value]) => {
            form.elements[field].value = value;
        });
    }

    if (button && birthday && search) {
        button.hidden = false;

        button.addEventListener('click', () => {
            const choice = randomChoice();

            fillFields(button.form, choice);
            birthday.dispatchEvent(new CustomEvent('set-date', { detail: choice.date.iso }));
            search.value = choice.label;

            // Lets form.js clear any messages for the fields that are now filled in.
            search.dispatchEvent(new Event('change', { bubbles: true }));

            if (note) {
                note.textContent = `A random day: ${choice.date.text}, in ${choice.label}.` + (nameInput.value.trim() === '' ? ' Add your name, then continue.' : '');
                note.hidden = false;
            }

            if (nameInput.value.trim() === '') {
                nameInput.focus();
            }
        });
    }

    // On the day page: keep the name, pick a new day and place, and start again.
    if (again && again.form) {
        again.hidden = false;

        again.addEventListener('click', () => {
            fillFields(again.form, randomChoice());
            again.disabled = true;
            again.classList.add('is-loading');
            again.setAttribute('aria-busy', 'true');
            again.textContent = 'Picking a day';
            again.form.submit();
        });
    }
})();
