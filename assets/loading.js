// Loading screen: asks build.php to gather the day and shows each step as it finishes,
// then moves on to the finished page. If progress can't be streamed, the steps just wait and
// the page still moves on when the build ends.
(function () {
    const root = document.querySelector('.loading');

    if (!root) {
        return;
    }

    const csrf = root.dataset.csrf;
    const total = Number(root.dataset.total) || 1;
    const bar = document.getElementById('loading-bar');
    const meter = document.getElementById('loading-meter');
    const title = document.getElementById('loading-title');
    const note = document.getElementById('loading-note');
    const live = document.getElementById('loading-live');
    const failed = document.getElementById('loading-failed');
    const errorText = document.getElementById('loading-error');
    const retry = document.getElementById('loading-retry');
    const steps = Array.from(root.querySelectorAll('[data-step]'));

    let finished = 0;

    function setProgress(count) {
        const percent = Math.round((count / total) * 100);
        // Never show a completely empty bar once work has begun.
        bar.style.transform = 'scaleX(' + Math.max(percent, 4) / 100 + ')';
        meter.setAttribute('aria-valuenow', String(percent));
    }

    function mark(key, state) {
        const item = root.querySelector('[data-step="' + key + '"]');

        if (!item) {
            return;
        }

        item.dataset.state = state;

        if (state === 'done') {
            finished += 1;
            setProgress(finished);
        } else if (state === 'active') {
            live.textContent = item.querySelector('.label').textContent + '.';
        }
    }

    function reset() {
        finished = 0;
        steps.forEach((item) => (item.dataset.state = 'waiting'));
        root.classList.remove('is-failed');
        failed.hidden = true;
        title.firstChild.textContent = 'Developing your day';
        setProgress(0);
    }

    function fail(message) {
        root.classList.add('is-failed');
        title.firstChild.textContent = 'Something went wrong';
        errorText.textContent = message;
        failed.hidden = false;
        retry.focus();
    }

    function finish() {
        steps.forEach((item) => (item.dataset.state = 'done'));
        setProgress(total);
        root.classList.add('is-done');
        title.firstChild.textContent = 'Ready';
        note.textContent = 'Here it comes.';
        live.textContent = 'Your day is ready.';

        // A short pause so the full bar is seen, then fade out and go.
        setTimeout(() => {
            root.classList.add('is-leaving');
            setTimeout(() => location.replace('/result'), 500);
        }, 500);
    }

    function handle(line) {
        let message;

        try {
            message = JSON.parse(line);
        } catch (error) {
            return false;
        }

        if (message.step) {
            mark(message.step, message.state === 'start' ? 'active' : 'done');
        } else if (message.done) {
            finish();
            return true;
        } else if (message.error) {
            fail(message.error);
            return true;
        }

        return false;
    }

    async function run() {
        reset();

        const body = new FormData();
        body.append('csrf', csrf);

        let ended = false;

        try {
            const response = await fetch('/build', { method: 'POST', body: body, credentials: 'same-origin' });

            if (!response.ok) {
                throw new Error('Request failed: ' + response.status);
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';

            for (;;) {
                const { value, done } = await reader.read();

                if (value) {
                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop();

                    lines.forEach((line) => {
                        if (line.trim() !== '' && handle(line)) {
                            ended = true;
                        }
                    });
                }

                if (done) {
                    break;
                }
            }

            if (buffer.trim() !== '' && handle(buffer)) {
                ended = true;
            }
        } catch (error) {
            console.warn('Loading failed', error);
        }

        // The stream closed without a final message: the connection dropped or the server stopped early.
        if (!ended) {
            fail('The connection dropped before your day was ready. Your details are still saved.');
        }
    }

    retry.addEventListener('click', run);
    run();
})();
