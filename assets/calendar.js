// Calendar for the birthday field. It replaces the browser's small date picker with a visible
// month grid, and writes the chosen date into the original date input, so the form submits as before.
(function () {
    const input = document.getElementById('birthday-native');

    if (!input) {
        return;
    }

    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const minIso = input.min;
    const maxIso = input.max;
    const minYear = Number(minIso.slice(0, 4));
    const maxYear = Number(maxIso.slice(0, 4));
    const minMonthKey = Number(minIso.slice(0, 4)) * 12 + Number(minIso.slice(5, 7)) - 1;
    const maxMonthKey = Number(maxIso.slice(0, 4)) * 12 + Number(maxIso.slice(5, 7)) - 1;

    // The calendar enforces a choice, so the native required check isn't needed.
    // The server still rejects an empty birthday.
    input.required = false;

    function parse(iso) {
        const [y, m, d] = iso.split('-').map(Number);

        return { y, m: m - 1, d };
    }

    function toIso(y, m, d) {
        return `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    }

    function label(iso) {
        const p = parse(iso);

        return `${p.d} ${MONTHS[p.m]} ${p.y}`;
    }

    function element(tag, className, attributes = {}) {
        const el = document.createElement(tag);

        if (className) {
            el.className = className;
        }

        Object.entries(attributes).forEach(([key, value]) => el.setAttribute(key, value));

        return el;
    }

    let selected = input.value || null;
    const start = selected ? parse(selected) : { y: 1990, m: 0, d: 1 };
    let view = { y: start.y, m: start.m };

    // Build the widget.
    const wrap = element('div', 'date-picker');

    const toggle = element('button', 'date-picker-toggle', {
        type: 'button',
        'aria-haspopup': 'dialog',
        'aria-expanded': 'false',
    });
    toggle.textContent = selected ? label(selected) : 'Choose your birthday';

    const panel = element('div', 'date-picker-panel', {
        role: 'dialog',
        'aria-label': 'Choose your birthday',
    });
    panel.hidden = true;

    const header = element('div', 'date-picker-header');
    const prev = element('button', 'date-picker-nav', { type: 'button', 'aria-label': 'Previous month' });
    prev.textContent = '‹';
    const next = element('button', 'date-picker-nav', { type: 'button', 'aria-label': 'Next month' });
    next.textContent = '›';

    const monthSelect = element('select', 'date-picker-select', { 'aria-label': 'Month' });
    MONTHS.forEach((name, i) => monthSelect.add(new Option(name, String(i))));

    const yearSelect = element('select', 'date-picker-select', { 'aria-label': 'Year' });
    // Newest first, since most people are looking for a recent year.
    for (let y = maxYear; y >= minYear; y--) {
        yearSelect.add(new Option(String(y), String(y)));
    }

    header.append(prev, monthSelect, yearSelect, next);

    const weekdayRow = element('div', 'date-picker-weekdays');
    WEEKDAYS.forEach((name) => {
        const cell = element('span');
        cell.textContent = name;
        weekdayRow.appendChild(cell);
    });

    const grid = element('div', 'date-picker-grid', { role: 'grid' });

    panel.append(header, weekdayRow, grid);
    wrap.append(toggle, panel);
    input.insertAdjacentElement('beforebegin', wrap);

    const now = new Date();
    const todayIso = toIso(now.getFullYear(), now.getMonth(), now.getDate());

    function render() {
        monthSelect.value = String(view.m);
        yearSelect.value = String(view.y);

        const monthKey = view.y * 12 + view.m;
        prev.disabled = monthKey <= minMonthKey;
        next.disabled = monthKey >= maxMonthKey;

        const firstWeekday = new Date(Date.UTC(view.y, view.m, 1)).getUTCDay();
        const daysInMonth = new Date(Date.UTC(view.y, view.m + 1, 0)).getUTCDate();

        grid.replaceChildren();

        for (let i = 0; i < firstWeekday; i++) {
            grid.appendChild(element('span', null, { 'aria-hidden': 'true' }));
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const iso = toIso(view.y, view.m, d);
            const day = element('button', 'date-picker-day', {
                type: 'button',
                'aria-label': label(iso),
                'data-iso': iso,
            });
            day.textContent = String(d);
            day.disabled = iso < minIso || iso > maxIso;

            if (iso === selected) {
                day.classList.add('selected');
                day.setAttribute('aria-pressed', 'true');
            }

            if (iso === todayIso) {
                day.classList.add('today');
            }

            grid.appendChild(day);
        }
    }

    function shift(delta) {
        let month = view.m + delta;
        let year = view.y;

        if (month < 0) {
            month = 11;
            year--;
        } else if (month > 11) {
            month = 0;
            year++;
        }

        view = { y: year, m: month };
        render();
    }

    function open() {
        render();
        panel.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');

        const target = grid.querySelector('.selected') || grid.querySelector('.date-picker-day:not(:disabled)');
        target?.focus();
    }

    function close(refocus) {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');

        if (refocus) {
            toggle.focus();
        }
    }

    function choose(iso) {
        selected = iso;
        input.value = iso;
        input.dispatchEvent(new Event('change', { bubbles: true }));
        toggle.textContent = label(iso);
        close(true);
    }

    toggle.addEventListener('click', () => (panel.hidden ? open() : close(false)));
    prev.addEventListener('click', () => shift(-1));
    next.addEventListener('click', () => shift(1));

    monthSelect.addEventListener('change', () => {
        view.m = Number(monthSelect.value);
        render();
    });

    yearSelect.addEventListener('change', () => {
        view.y = Number(yearSelect.value);
        render();
    });

    grid.addEventListener('click', (event) => {
        const day = event.target.closest('.date-picker-day');

        if (day && !day.disabled) {
            choose(day.dataset.iso);
        }
    });

    // Arrow keys move between days; Escape closes the calendar.
    panel.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close(true);
            return;
        }

        const day = event.target.closest('.date-picker-day');
        const steps = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };

        if (!day || !(event.key in steps)) {
            return;
        }

        event.preventDefault();

        const p = parse(day.dataset.iso);
        const moved = new Date(Date.UTC(p.y, p.m, p.d + steps[event.key]));
        const iso = toIso(moved.getUTCFullYear(), moved.getUTCMonth(), moved.getUTCDate());

        if (iso < minIso || iso > maxIso) {
            return;
        }

        view = { y: moved.getUTCFullYear(), m: moved.getUTCMonth() };
        render();
        grid.querySelector(`[data-iso="${iso}"]`)?.focus();
    });

    document.addEventListener('click', (event) => {
        if (!panel.hidden && !wrap.contains(event.target)) {
            close(false);
        }
    });

    toggle.setAttribute('aria-controls', 'birthday-panel');
    panel.id = 'birthday-panel';
})();
