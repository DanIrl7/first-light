// The "click to begin" cover. It sits over the day page while the music video loads, then the click that
// dismisses it is also what lets the browser play sound. music.js tells this file when the video is ready.
// While the cover is up, <html> has the class has-gate: the page can't be scrolled, and its content is inert.
(function () {
    const root = document.documentElement;
    const gate = document.getElementById('gate');

    if (!gate || !root.classList.contains('has-gate')) {
        return;
    }

    const SKIP_AFTER = 30000; // the "step out without sound" link only appears after this long
    const FADE = 900; // matches the fade-out in music.css
    const LINE_EVERY = 4500; // how often the waiting message changes

    // What the cover says while it waits. It stays in the time machine's world and never names the music.
    const WAITING_LINES = [
        'Calibrating the chronometer…',
        'Aligning the dials…',
        'Steadying the temporal field…',
        'Checking the landing gear…',
        'Warming up the engines…',
        'Plotting the final approach…',
    ];
    const content = document.getElementById('smooth-wrapper');
    const start = document.getElementById('gate-start');
    const skip = document.getElementById('gate-skip');
    const note = document.getElementById('gate-note');

    let opened = false;
    let settled = false; // the video is ready, or loading failed
    let skipTimer = null;
    let lineTimer = null;
    let line = 0;

    // Screen readers shouldn't read out every changing line, only the one that says what to do next.
    note.setAttribute('aria-live', 'off');

    function stopWaitingLines() {
        clearInterval(lineTimer);
        note.setAttribute('aria-live', 'polite');
    }

    // Keeps keyboard and screen reader users inside the cover until it's gone.
    if (content) {
        content.inert = true;
    }

    function open() {
        if (opened) {
            return;
        }

        opened = true;
        clearTimeout(skipTimer);
        clearInterval(lineTimer);
        clearTimeout(missingTimer);
        root.classList.add('gate-opening');

        if (content) {
            content.inert = false;
        }

        // cinema.js starts the opening shot now, as the cover fades.
        window.dispatchEvent(new Event('gate:open'));

        setTimeout(() => {
            root.classList.remove('has-gate', 'gate-opening');
            gate.hidden = true;

            const toggle = document.getElementById('sound-toggle');

            if (toggle && !toggle.hidden) {
                toggle.focus({ preventScroll: true });
            }
        }, FADE);
    }

    start.addEventListener('click', () => {
        // music.js turns the sound on right here, inside the click, which is what browsers require.
        window.dispatchEvent(new Event('gate:sound'));
        open();
    });

    skip.addEventListener('click', open);

    window.bornGate = {
        // music.js sets this, so the cover isn't left up for good if that script never runs.
        attached: false,

        // The video is playing (or will start with the click).
        ready() {
            if (opened) {
                return;
            }

            settled = true;
            stopWaitingLines();
            start.disabled = false;
            start.classList.remove('is-loading');
            start.textContent = 'Open the hatch';
            note.textContent = 'All systems are go. Open the hatch when you\'re ready.';
            start.focus({ preventScroll: true });
        },

        // The video can't be played at all, so there is nothing to wait for. The only way forward is to step out,
        // so that link shows at once, whatever the clock says.
        fail() {
            if (opened) {
                return;
            }

            settled = true;
            clearTimeout(skipTimer);
            stopWaitingLines();
            start.hidden = true;
            note.textContent = 'The machine landed safely. Step out whenever you\'re ready.';
            skip.hidden = false;
            skip.classList.add('is-primary');
            skip.focus({ preventScroll: true });
        },
    };

    // The waiting message changes every few seconds, so a long wait still feels like something is happening.
    lineTimer = setInterval(() => {
        line = (line + 1) % WAITING_LINES.length;
        note.textContent = WAITING_LINES[line];
    }, LINE_EVERY);

    // The way out appears 30 seconds in, whether or not the video is ready, and not before.
    skipTimer = setTimeout(() => {
        if (!opened && !settled) {
            stopWaitingLines();
            note.textContent = 'The machine\'s running slow today.';
        }

        if (!opened) {
            skip.hidden = false;
        }
    }, SKIP_AFTER);

    const missingTimer = setTimeout(() => {
        if (!window.bornGate.attached) {
            open();
        }
    }, 10000);
})();
