// Birth place search. Suggestions come from Open-Meteo's geocoding API, and the chosen place's
// coordinates, timezone, and names go into hidden fields that the form submits.
(function () {
    const search = document.getElementById('place-search');
    const list = document.getElementById('place-results');

    if (!search || !list) {
        return;
    }

    const API = 'https://geocoding-api.open-meteo.com/v1/search';
    const FIELDS = {
        name: 'place_name',
        admin1: 'place_region',
        country: 'place_country',
        country_code: 'place_country_code',
        latitude: 'place_latitude',
        longitude: 'place_longitude',
        timezone: 'place_timezone',
    };

    let results = [];
    let active = -1;
    let timer = null;
    let controller = null;

    function setField(id, value) {
        document.getElementById(id).value = value;
    }

    function clearSelection() {
        Object.values(FIELDS).forEach((id) => setField(id, ''));
    }

    function labelFor(place) {
        return [place.name, place.admin1, place.country].filter(Boolean).join(', ');
    }

    function hide() {
        list.hidden = true;
        search.setAttribute('aria-expanded', 'false');
        search.removeAttribute('aria-activedescendant');
        active = -1;
    }

    function showMessage(text) {
        const message = document.createElement('div');
        message.className = 'place-message';
        message.textContent = text;
        list.replaceChildren(message);
        list.hidden = false;
        search.setAttribute('aria-expanded', 'true');
    }

    function setActive(index) {
        const options = list.querySelectorAll('[role="option"]');

        options.forEach((option, i) => {
            option.setAttribute('aria-selected', i === index ? 'true' : 'false');
        });

        active = index;
        search.setAttribute('aria-activedescendant', options[index].id);
    }

    function choose(index) {
        const place = results[index];

        setField(FIELDS.name, place.name);
        setField(FIELDS.admin1, place.admin1 || '');
        setField(FIELDS.country, place.country || '');
        setField(FIELDS.country_code, place.country_code || '');
        setField(FIELDS.latitude, place.latitude);
        setField(FIELDS.longitude, place.longitude);
        setField(FIELDS.timezone, place.timezone);

        search.value = labelFor(place);
        hide();
    }

    function render() {
        if (results.length === 0) {
            showMessage('No landing sites match. Try a different spelling.');
            return;
        }

        const options = results.map((place, i) => {
            const option = document.createElement('div');
            option.className = 'place-option';
            option.id = `place-option-${i}`;
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            option.textContent = labelFor(place);
            // mousedown rather than click, so the search box doesn't lose focus first.
            option.addEventListener('mousedown', (event) => {
                event.preventDefault();
                choose(i);
            });
            return option;
        });

        list.replaceChildren(...options);
        list.hidden = false;
        search.setAttribute('aria-expanded', 'true');
        active = -1;
    }

    async function lookup(query) {
        if (controller) {
            controller.abort();
        }

        controller = new AbortController();
        const mine = controller;

        // Show that a search is running, so a slow connection doesn't look like nothing happened.
        showMessage('Scanning the map…');
        search.setAttribute('aria-busy', 'true');

        const params = new URLSearchParams({ name: query, count: 8, language: 'en', format: 'json' });

        try {
            const response = await fetch(`${API}?${params}`, { signal: controller.signal });

            if (!response.ok) {
                throw new Error('Search failed');
            }

            const data = await response.json();
            results = Array.isArray(data.results) ? data.results : [];
            render();
        } catch (error) {
            if (error.name !== 'AbortError') {
                results = [];
                showMessage('The map scanner is offline. Try again in a moment.');
            }
        } finally {
            // A newer search has its own controller; only the latest one clears the busy state.
            if (controller === mine) {
                search.removeAttribute('aria-busy');
            }
        }
    }

    search.addEventListener('input', () => {
        // Editing the text after choosing a place means the old choice no longer applies.
        clearSelection();
        clearTimeout(timer);

        const query = search.value.trim();

        if (query.length < 2) {
            results = [];
            hide();
            return;
        }

        timer = setTimeout(() => lookup(query), 250);
    });

    search.addEventListener('keydown', (event) => {
        if (list.hidden || results.length === 0) {
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActive((active + 1) % results.length);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive(active <= 0 ? results.length - 1 : active - 1);
        } else if (event.key === 'Enter' && active >= 0) {
            event.preventDefault();
            choose(active);
        } else if (event.key === 'Escape') {
            hide();
        }
    });

    document.addEventListener('click', (event) => {
        if (!list.hidden && !search.contains(event.target) && !list.contains(event.target)) {
            hide();
        }
    });
})();
