// Clock: maps how far down the page you've scrolled onto the 24 hours of the birth date,
// so the top of the page reads 00:00 and the bottom reads 23:59. The hands ease toward the target
// instead of jumping, so a fast scroll sweeps through the hours.
(function () {
    const clockTime = document.getElementById('clock-time');

    if (!clockTime) {
        return;
    }

    let target = 0;
    let shown = 0;
    let running = false;

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function draw(minutes) {
        const whole = Math.min(1439, Math.max(0, Math.round(minutes)));
        clockTime.textContent = pad(Math.floor(whole / 60)) + ':' + pad(whole % 60);
    }

    function step() {
        const gap = target - shown;

        if (Math.abs(gap) < 0.5) {
            shown = target;
            running = false;
            draw(shown);
            return;
        }

        shown += gap * 0.18;
        draw(shown);
        requestAnimationFrame(step);
    }

    function update() {
        const scrollable = document.documentElement.scrollHeight - window.innerHeight;
        const progress = scrollable > 0 ? window.scrollY / scrollable : 0;
        target = Math.min(1439, Math.max(0, progress * 1439));

        if (!running) {
            running = true;
            requestAnimationFrame(step);
        }
    }

    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
})();
