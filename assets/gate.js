// The "click to begin" cover. It sits over the day page while the music video loads, then the click that
// dismisses it is also what lets the browser play sound. music.js tells this file when the video is ready.
// While the cover is up, <html> has the class has-gate: the page can't be scrolled, and its content is inert.
(function () {
    const root = document.documentElement;
    const gate = document.getElementById('gate');

    if (!gate || !root.classList.contains('has-gate')) {
        return;
    }

    const SLOW_AFTER = 30000; // after this long without a playing video, offer to go on without sound
    const FADE = 900; // matches the fade-out in music.css
    const content = document.getElementById('smooth-wrapper');
    const start = document.getElementById('gate-start');
    const skip = document.getElementById('gate-skip');
    const note = document.getElementById('gate-note');

    let opened = false;
    let settled = false; // the video is ready, or loading failed
    let slowTimer = null;

    // Keeps keyboard and screen reader users inside the cover until it's gone.
    if (content) {
        content.inert = true;
    }

    function open() {
        if (opened) {
            return;
        }

        opened = true;
        clearTimeout(slowTimer);
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
            clearTimeout(slowTimer);
            start.disabled = false;
            start.classList.remove('is-loading');
            start.textContent = 'Click to begin';
            note.textContent = 'Best with the sound on.';
            skip.hidden = false;
            start.focus({ preventScroll: true });
        },

        // The video can't be played at all, so there is nothing to wait for.
        fail(message) {
            if (opened) {
                return;
            }

            settled = true;
            clearTimeout(slowTimer);
            start.hidden = true;
            note.textContent = message;
            skip.hidden = false;
            skip.classList.add('is-primary');
            skip.focus({ preventScroll: true });
        },
    };

    slowTimer = setTimeout(() => {
        if (!settled && !opened) {
            note.textContent = 'This is taking longer than usual.';
            skip.hidden = false;
        }
    }, SLOW_AFTER);

    const missingTimer = setTimeout(() => {
        if (!window.bornGate.attached) {
            open();
        }
    }, 10000);
})();
