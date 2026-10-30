// Scroll choreography for the day page, built on GSAP and ScrollTrigger.
// Every animation lives inside gsap.matchMedia(), so visitors who prefer reduced motion get the
// finished page with no movement. If GSAP fails to load, nothing is hidden and the page still reads.
(function () {
    if (!window.gsap || !window.ScrollTrigger) {
        return;
    }

    gsap.registerPlugin(ScrollTrigger);

    if (window.ScrollSmoother) {
        gsap.registerPlugin(ScrollSmoother);
    }

    // Wraps every word of an element in a span so words can be animated one by one.
    // With a mask, each word is clipped so it can rise into place; without one, words are plain spans.
    function splitWords(el, mask = true) {
        const key = mask ? 'splitMasked' : 'splitPlain';

        if (el.dataset[key]) {
            return el.querySelectorAll(mask ? '.word-inner' : '.rw');
        }

        const walk = (node) => {
            Array.from(node.childNodes).forEach((child) => {
                if (child.nodeType === Node.TEXT_NODE) {
                    const fragment = document.createDocumentFragment();

                    child.textContent.split(/(\s+)/).forEach((part) => {
                        if (part === '') {
                            return;
                        }

                        if (/^\s+$/.test(part)) {
                            fragment.appendChild(document.createTextNode(' '));
                            return;
                        }

                        if (mask) {
                            const word = document.createElement('span');
                            const inner = document.createElement('span');
                            word.className = 'word';
                            inner.className = 'word-inner';
                            inner.textContent = part;
                            word.appendChild(inner);
                            fragment.appendChild(word);
                        } else {
                            const word = document.createElement('span');
                            word.className = 'rw';
                            word.textContent = part;
                            fragment.appendChild(word);
                        }
                    });

                    node.replaceChild(fragment, child);
                } else if (child.nodeType === Node.ELEMENT_NODE) {
                    walk(child);
                }
            });
        };

        walk(el);
        el.dataset[key] = '1';

        return el.querySelectorAll(mask ? '.word-inner' : '.rw');
    }

    const motion = gsap.matchMedia();

    motion.add('(prefers-reduced-motion: no-preference)', () => {
        const hero = document.querySelector('.scene-hero');

        // Smooth, eased scrolling with GSAP's ScrollSmoother. It must exist before any ScrollTrigger, so it comes
        // first. Elements with data-speed drift at a different pace to the page, and data-lag makes them trail
        // behind it. Touch screens keep their native scrolling, which already has momentum.
        let smoother = null;

        if (window.ScrollSmoother && document.getElementById('smooth-wrapper')) {
            smoother = ScrollSmoother.create({
                wrapper: '#smooth-wrapper',
                content: '#smooth-content',
                smooth: 1.2,
                effects: true,
                smoothTouch: false,
            });
        }

        // Scroll progress along the top edge.
        const bar = document.querySelector('.progress span');

        if (bar) {
            gsap.to(bar, {
                scaleX: 1,
                ease: 'none',
                // Refreshed last, so the page height already includes the pinned scene's spacing.
                scrollTrigger: { start: 0, end: 'max', scrub: 0.3, refreshPriority: 99 },
            });
        }

        // The sky behind the page follows the clock: night at the top, dawn at 06:00, bright at noon,
        // dusk at 18:00, and night again at the bottom. Layers cross-fade, which keeps scrolling smooth.
        if (document.querySelector('.sky')) {
            // Midnight at the top of the page.
            gsap.set('.sky-night', { opacity: 1 });
            gsap.set('.sky-dawn, .sky-noon, .sky-dusk', { opacity: 0 });

            const sky = gsap.timeline({
                defaults: { ease: 'none', duration: 1 },
                scrollTrigger: { start: 0, end: 'max', scrub: 0.8, refreshPriority: 99 },
            });

            // One unit of the timeline is six hours of the day.
            sky
                .to('.sky-night', { opacity: 0 }, 0)
                .to('.sky-dawn', { opacity: 1 }, 0)
                .to('.sky-dawn', { opacity: 0 }, 1)
                .to('.sky-noon', { opacity: 1 }, 1)
                .to('.sky-noon', { opacity: 0 }, 2)
                .to('.sky-dusk', { opacity: 1 }, 2)
                .to('.sky-dusk', { opacity: 0 }, 3)
                .to('.sky-night', { opacity: 1 }, 3);

            // The sun climbs from the left horizon at 06:00, tops out at noon and sets on the right at 18:00.
            const orb = document.querySelector('.sky-orb');

            if (orb) {
                const setX = gsap.quickSetter(orb, 'x', 'px');
                const setY = gsap.quickSetter(orb, 'y', 'px');
                const place = (progress) => {
                    const angle = (progress - 0.5) * Math.PI * 2;
                    setX(window.innerWidth * (0.5 + 0.58 * Math.sin(angle)));
                    setY(window.innerHeight * (1.05 - 0.78 * Math.cos(angle)));
                };

                ScrollTrigger.create({
                    start: 0,
                    end: 'max',
                    refreshPriority: 99,
                    onUpdate: (self) => place(self.progress),
                    onRefresh: (self) => place(self.progress),
                });

                place(0);
            }
        }

        // Opening shot: the photo settles, then the headline rises word by word.
        if (hero) {
            const title = hero.querySelector('[data-split]');
            const words = title ? splitWords(title) : [];
            // While the "click to begin" cover is up (gate.js), the opening shot waits behind it.
            const gated = document.documentElement.classList.contains('has-gate');
            const intro = gsap.timeline({ paused: gated, defaults: { ease: 'power4.out' } });

            if (gated) {
                window.addEventListener('gate:open', () => intro.play(), { once: true });
            }

            intro
                .from('.hero-img', { scale: 1.25, duration: 2.6, ease: 'power2.out' }, 0)
                .from('.hero-copy .eyebrow', { y: 20, opacity: 0, duration: 0.9 }, 0.3)
                .from(words, { yPercent: 115, duration: 1.3, stagger: 0.09 }, 0.45)
                .from('.hero-copy .meta', { y: 20, opacity: 0, duration: 1 }, 1.2)
                .from('.scroll-cue', { opacity: 0, duration: 1 }, 1.6);

            // The photo drifts slower than the page, and the headline lifts away as you leave.
            gsap.to('.hero-media', {
                yPercent: 18,
                ease: 'none',
                scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true },
            });

            gsap.to('.hero-copy', {
                yPercent: -22,
                scale: 0.94,
                opacity: 0,
                ease: 'none',
                scrollTrigger: { trigger: hero, start: 'top top', end: '70% top', scrub: true },
            });
        }

        // Section headings rise word by word as they come into view.
        gsap.utils.toArray('.scene:not(.scene-hero) [data-split]').forEach((heading) => {
            gsap.from(splitWords(heading), {
                yPercent: 115,
                duration: 1.1,
                stagger: 0.07,
                ease: 'power4.out',
                scrollTrigger: { trigger: heading, start: 'top 88%', once: true },
            });
        });

        // Reading text starts dim and brightens word by word as you scroll through it.
        gsap.utils.toArray('[data-scrub]').forEach((block) => {
            gsap.fromTo(splitWords(block, false), { opacity: 0.16 }, {
                opacity: 1,
                ease: 'none',
                stagger: 0.1,
                scrollTrigger: { trigger: block, start: 'top 82%', end: 'bottom 52%', scrub: true },
            });
        });

        // Anything marked data-reveal fades up, in small groups as they arrive.
        const reveals = gsap.utils.toArray('[data-reveal]');

        if (reveals.length) {
            gsap.set(reveals, { y: 40, opacity: 0 });

            ScrollTrigger.batch(reveals, {
                start: 'top 90%',
                once: true,
                onEnter: (batch) => gsap.to(batch, {
                    y: 0,
                    opacity: 1,
                    duration: 1,
                    stagger: 0.12,
                    ease: 'power3.out',
                    overwrite: true,
                }),
            });
        }

        // The temperature counts up to its value.
        const temp = document.querySelector('[data-count]');

        if (temp) {
            const target = parseFloat(temp.dataset.count);
            const decimals = parseInt(temp.dataset.decimals || '0', 10);
            const counter = { value: 0 };

            gsap.to(counter, {
                value: target,
                duration: 1.8,
                ease: 'power2.out',
                onUpdate: () => {
                    temp.textContent = counter.value.toFixed(decimals);
                },
                scrollTrigger: { trigger: temp, start: 'top 85%', once: true },
            });
        }

        // The birth year, drawn huge and outlined, slides sideways behind the events as you scroll.
        const ghost = document.querySelector('.ghost span');

        if (ghost) {
            gsap.fromTo(ghost, { xPercent: 4 }, {
                xPercent: -42,
                ease: 'none',
                scrollTrigger: { trigger: '.scene-events', start: 'top bottom', end: 'bottom top', scrub: true },
            });
        }

        // Each event: the photo is wiped in, then the year and text follow. The photo also drifts as you scroll.
        gsap.utils.toArray('.event').forEach((event) => {
            const media = event.querySelector('.event-media');
            const image = media ? media.querySelector('img') : null;
            const body = event.querySelectorAll('.event-body > *');

            const tl = gsap.timeline({
                defaults: { ease: 'power3.out' },
                scrollTrigger: { trigger: event, start: 'top 78%', once: true },
            });

            if (media) {
                tl.fromTo(media, { clipPath: 'inset(0 0 100% 0)' }, { clipPath: 'inset(0 0 0% 0)', duration: 1.3, ease: 'power4.inOut' }, 0);
            }

            tl.from(body, { y: 36, opacity: 0, duration: 0.9, stagger: 0.1 }, media ? 0.35 : 0);

            if (image) {
                gsap.fromTo(image, { yPercent: -8 }, {
                    yPercent: 8,
                    ease: 'none',
                    scrollTrigger: { trigger: media, start: 'top bottom', end: 'bottom top', scrub: true },
                });
            }
        });

        // Photos lean slightly in the direction of a fast scroll and settle back when it slows.
        const leaning = gsap.utils.toArray('.event-media, .person-photo');

        if (leaning.length) {
            const lean = { value: 0 };
            const setLean = gsap.quickSetter(leaning, 'skewY', 'deg');
            const limit = gsap.utils.clamp(-2.5, 2.5);

            ScrollTrigger.create({
                onUpdate: (self) => {
                    const target = limit(self.getVelocity() / -450);

                    if (Math.abs(target) > Math.abs(lean.value)) {
                        lean.value = target;
                        gsap.to(lean, {
                            value: 0,
                            duration: 0.9,
                            ease: 'power3.out',
                            overwrite: true,
                            onUpdate: () => setLean(lean.value),
                        });
                    }
                },
            });
        }

        // The music video opens up from an inset frame to full width, like a screen filling your view.
        const video = document.querySelector('.video');

        if (video) {
            gsap.fromTo(video, { clipPath: 'inset(10% 12% 10% 12% round 14px)' }, {
                clipPath: 'inset(0% 0% 0% 0% round 0px)',
                ease: 'none',
                scrollTrigger: { trigger: video, start: 'top 95%', end: 'top 25%', scrub: 0.6 },
            });
        }

        // The certificate settles into place, and its seal is drawn stroke by stroke.
        const certificate = document.querySelector('.certificate');

        if (certificate) {
            const seal = certificate.querySelectorAll('.seal circle, .seal path');
            const reveal = gsap.timeline({
                defaults: { ease: 'power3.out' },
                scrollTrigger: { trigger: certificate, start: 'top 82%', once: true },
            });

            reveal.from(certificate, { y: 60, scale: 0.94, opacity: 0, duration: 1.3 }, 0);

            if (seal.length) {
                reveal.fromTo(seal, { strokeDashoffset: 1 }, { strokeDashoffset: 0, duration: 1.4, stagger: 0.3, ease: 'power2.inOut' }, 0.5);
            }
        }

        // Undone if the visitor switches to reduced motion while the page is open.
        return () => {
            if (smoother) {
                smoother.kill();
            }
        };
    });

    // On wide screens the people scene is pinned and the strip of portraits slides sideways as you scroll.
    // On narrow screens it stays a swipeable strip.
    motion.add('(prefers-reduced-motion: no-preference) and (min-width: 900px)', () => {
        const scene = document.querySelector('.scene-people');
        const track = scene ? scene.querySelector('.people-track') : null;

        if (!scene || !track || track.children.length < 2) {
            return;
        }

        scene.classList.add('is-pinned');

        const distance = () => Math.max(0, track.scrollWidth - scene.clientWidth);

        gsap.to(track, {
            x: () => -distance(),
            ease: 'none',
            scrollTrigger: {
                trigger: scene,
                start: 'top top',
                end: () => '+=' + distance(),
                pin: true,
                scrub: 0.6,
                invalidateOnRefresh: true,
                anticipatePin: 1,
                // Refreshed first, so the triggers below it (created earlier) measure after its pin spacing.
                refreshPriority: -1,
            },
        });

        return () => scene.classList.remove('is-pinned');
    });

    // Images and fonts change the page height, so positions are measured again once they land.
    window.addEventListener('load', () => ScrollTrigger.refresh());

    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(() => ScrollTrigger.refresh());
    }
})();
