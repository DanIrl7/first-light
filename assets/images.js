// Image loading states. While a photo is still coming in, its frame shows a soft shimmer; when it
// arrives the photo fades in, and if it never does the frame just stays quiet. Without JavaScript
// none of the classes are added, so the images behave as plain images.
(function () {
    const images = document.querySelectorAll('.hero-img, .event-media img, .person-photo, .leader img');

    images.forEach((img) => {
        if (img.tagName !== 'IMG') {
            return;
        }

        const frame = img.closest('.event-media') || img;

        function loaded() {
            img.classList.remove('is-loading');
            frame.classList.remove('is-loading');
        }

        function broken() {
            loaded();
            img.classList.add('is-broken');
            frame.classList.add('is-broken');
        }

        // Already finished before this script ran (cached): nothing to show.
        if (img.complete) {
            if (img.naturalWidth === 0) {
                broken();
            }

            return;
        }

        img.classList.add('is-loading');
        frame.classList.add('is-loading');
        img.addEventListener('load', loaded, { once: true });
        img.addEventListener('error', broken, { once: true });
    });
})();
