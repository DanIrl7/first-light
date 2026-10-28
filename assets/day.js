// Clock: maps how far down the page you've scrolled onto the 24 hours of the birth date,
// so the top of the page reads 00:00 and the bottom reads 23:59.
(function () {
    const clockTime = document.getElementById('clock-time');

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function updateClock() {
        const scrollable = document.documentElement.scrollHeight - window.innerHeight;
        const progress = scrollable > 0 ? window.scrollY / scrollable : 0;
        const minutes = Math.min(1439, Math.round(progress * 1439));
        clockTime.textContent = pad(Math.floor(minutes / 60)) + ':' + pad(minutes % 60);
    }

    window.addEventListener('scroll', updateClock, { passive: true });
    window.addEventListener('resize', updateClock);
    updateClock();
})();

// Fade each section in as it reaches the screen. Skipped when reduced motion is requested.
(function () {
    const panels = document.querySelectorAll('.panel');

    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        panels.forEach((panel) => panel.classList.add('in'));
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('in');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    panels.forEach((panel) => observer.observe(panel));
})();
