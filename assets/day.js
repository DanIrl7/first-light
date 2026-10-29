// Clock: maps how far down the page you've scrolled onto the 24 hours of the birth date,
// so the top of the page reads 00:00 and the bottom reads 23:59.
(function () {
    const clockTime = document.getElementById('clock-time');

    if (!clockTime) {
        return;
    }

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function updateClock() {
        const scrollable = document.documentElement.scrollHeight - window.innerHeight;
        const progress = scrollable > 0 ? window.scrollY / scrollable : 0;
        const minutes = Math.min(1439, Math.max(0, Math.round(progress * 1439)));
        clockTime.textContent = pad(Math.floor(minutes / 60)) + ':' + pad(minutes % 60);
    }

    window.addEventListener('scroll', updateClock, { passive: true });
    window.addEventListener('resize', updateClock);
    updateClock();
})();
