// "Share with someone", on the certificate: shows a link that opens this same day, and copies it.
// result.php puts the link's path in data-path; the full address is built here from wherever the page is served.
(function () {
    const share = document.getElementById('share');
    const open = document.getElementById('share-open');
    const panel = document.getElementById('share-panel');
    const field = document.getElementById('share-link');
    const copy = document.getElementById('share-copy');
    const status = document.getElementById('share-status');

    if (!share || !open || !panel || !field || !copy || !status) {
        return;
    }

    share.hidden = false;
    field.value = new URL(share.dataset.path, location.origin).href;

    let resetTimer = null;

    // The modern way where it's allowed, then the old way for browsers or pages that refuse it.
    async function copyLink() {
        try {
            await navigator.clipboard.writeText(field.value);

            return true;
        } catch (error) {
            field.focus();
            field.select();

            try {
                return document.execCommand('copy');
            } catch (fallbackError) {
                return false;
            }
        }
    }

    async function copyAndSay() {
        const worked = await copyLink();

        status.textContent = worked
            ? 'Link copied. Paste it anywhere to send this day to someone.'
            : 'The link is selected. Copy it by hand to send it on.';

        if (worked) {
            copy.textContent = 'Copied';
            clearTimeout(resetTimer);
            resetTimer = setTimeout(() => {
                copy.textContent = 'Copy link';
            }, 2200);
        }
    }

    open.addEventListener('click', () => {
        const opening = panel.hidden;

        panel.hidden = !opening;
        open.setAttribute('aria-expanded', String(opening));

        // Opening is a click, so the browser allows copying right away.
        if (opening) {
            field.select();
            copyAndSay();
        }
    });

    copy.addEventListener('click', copyAndSay);

    field.addEventListener('focus', () => field.select());
})();
