// Checks the start form in the browser before it is sent, so a missing birthday or place shows a message
// next to its field and nothing the visitor typed is lost. The server runs the same checks again.
(function () {
    const form = document.getElementById('born-form');

    if (!form) {
        return;
    }

    const nameInput = document.getElementById('field-name');
    const birthday = document.getElementById('birthday-native');
    const search = document.getElementById('place-search');
    const latitude = document.getElementById('place_latitude');
    const results = document.getElementById('place-results');

    // The calendar builds its toggle button after loading, so look it up when it's needed.
    const toggle = () => document.querySelector('.date-picker-toggle');

    function birthdayProblem() {
        const value = birthday.value;

        if (value === '') {
            return 'Pick a date on the calendar to set the dials.';
        }

        if (value < birthday.min || value > birthday.max) {
            return 'That\'s outside the machine\'s range. Pick a date between 1 January 1940 and today.';
        }

        return null;
    }

    function placeProblem() {
        if (latitude.value !== '') {
            return null;
        }

        return search.value.trim() === ''
            ? 'Start typing a city, then pick it from the list.'
            : 'Pick your city from the list that appears as you type.';
    }

    const checks = [
        {
            key: 'name',
            input: nameInput,
            focus: () => nameInput,
            problem: () => (nameInput.value.trim() === '' ? 'The logbook needs a name.' : null),
        },
        {
            key: 'birthday',
            input: birthday,
            focus: toggle,
            problem: birthdayProblem,
        },
        {
            key: 'place',
            input: search,
            focus: () => search,
            problem: placeProblem,
        },
    ];

    function show(check, message) {
        const error = document.getElementById('error-' + check.key);
        const flagged = [check.input, check.key === 'birthday' ? toggle() : null];

        error.textContent = message || '';
        error.hidden = !message;

        flagged.forEach((el) => {
            if (!el) {
                return;
            }

            if (message) {
                el.setAttribute('aria-invalid', 'true');
            } else {
                el.removeAttribute('aria-invalid');
            }
        });
    }

    // Once a field is fixed, its message goes away without waiting for the next submit.
    function clearFixed() {
        checks.forEach((check) => {
            const error = document.getElementById('error-' + check.key);

            if (!error.hidden && check.problem() === null) {
                show(check, '');
            }
        });
    }

    form.addEventListener('input', clearFixed);
    form.addEventListener('change', clearFixed);

    // Choosing a place from the list sets hidden fields without an input event, so look again just after.
    results.addEventListener('mousedown', () => setTimeout(clearFixed, 0));
    search.addEventListener('keydown', () => setTimeout(clearFixed, 0));

    form.addEventListener('submit', (event) => {
        let first = null;

        checks.forEach((check) => {
            const message = check.problem();
            show(check, message);

            if (message && first === null) {
                first = check;
            }
        });

        if (first !== null) {
            event.preventDefault();
            const target = first.focus();

            if (target) {
                target.focus();
            }

            return;
        }

        // Everything checks out: show that the form is being sent, and stop a second click from sending it twice.
        const button = form.querySelector('button[type="submit"]');

        if (button) {
            button.classList.add('is-loading');
            button.setAttribute('aria-busy', 'true');
            button.textContent = 'Powering up';
            // Disabled after the event, so this submit still goes through.
            setTimeout(() => (button.disabled = true), 0);
        }
    });

    // Coming back with the Back button can restore the page as it was, with the button still busy.
    window.addEventListener('pageshow', (event) => {
        const button = form.querySelector('button[type="submit"]');

        if (event.persisted && button) {
            button.disabled = false;
            button.classList.remove('is-loading');
            button.removeAttribute('aria-busy');
            button.textContent = 'Take me back';
        }
    });

    // A message from the server (after a failed submit) marks the calendar button too.
    if (document.getElementById('error-birthday') && !document.getElementById('error-birthday').hidden) {
        toggle()?.setAttribute('aria-invalid', 'true');
    }
})();
