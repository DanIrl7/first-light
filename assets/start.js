// Opening sequence for the start page: the title, intro and form rise in one after another.
// Skipped for visitors who prefer reduced motion, and harmless if GSAP didn't load.
(function () {
    if (!window.gsap) {
        return;
    }

    gsap.matchMedia().add('(prefers-reduced-motion: no-preference)', () => {
        gsap.from('[data-intro]', {
            y: 40,
            opacity: 0,
            duration: 1.1,
            stagger: 0.14,
            ease: 'power4.out',
        });

        gsap.from('.card', {
            y: 56,
            opacity: 0,
            duration: 1.2,
            delay: 0.5,
            ease: 'power4.out',
        });
    });
})();
