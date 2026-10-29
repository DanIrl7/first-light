// Scroll choreography for the day page, built on GSAP and ScrollTrigger.
// Every animation lives inside gsap.matchMedia(), so visitors who prefer reduced motion get the
// finished page with no movement. If GSAP fails to load, nothing is hidden and the page still reads.
(function () {
    if (!window.gsap || !window.ScrollTrigger) {
        return;
    }

    gsap.registerPlugin(ScrollTrigger);

    // Wraps every word of an element in a masked span so each word can rise into place.
    function splitWords(el) {
        if (el.dataset.splitDone) {
            return el.querySelectorAll('.word-inner');
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

                        const word = document.createElement('span');
                        const inner = document.createElement('span');
                        word.className = 'word';
                        inner.className = 'word-inner';
                        inner.textContent = part;
                        word.appendChild(inner);
                        fragment.appendChild(word);
                    });

                    node.replaceChild(fragment, child);
                } else if (child.nodeType === Node.ELEMENT_NODE) {
                    walk(child);
                }
            });
        };

        walk(el);
        el.dataset.splitDone = '1';

        return el.querySelectorAll('.word-inner');
    }

    const motion = gsap.matchMedia();

    motion.add('(prefers-reduced-motion: no-preference)', () => {
        const hero = document.querySelector('.scene-hero');

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

        // Opening shot: the photo settles, then the headline rises word by word.
        if (hero) {
            const title = hero.querySelector('[data-split]');
            const words = title ? splitWords(title) : [];
            const intro = gsap.timeline({ defaults: { ease: 'power4.out' } });

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

        // The music video opens up from an inset frame to full width, like a screen filling your view.
        const video = document.querySelector('.video');

        if (video) {
            gsap.fromTo(video, { clipPath: 'inset(10% 12% 10% 12% round 14px)' }, {
                clipPath: 'inset(0% 0% 0% 0% round 0px)',
                ease: 'none',
                scrollTrigger: { trigger: video, start: 'top 95%', end: 'top 25%', scrub: 0.6 },
            });
        }

        // The certificate settles into place.
        const certificate = document.querySelector('.certificate');

        if (certificate) {
            gsap.from(certificate, {
                y: 60,
                scale: 0.94,
                opacity: 0,
                duration: 1.3,
                ease: 'power3.out',
                scrollTrigger: { trigger: certificate, start: 'top 85%', once: true },
            });
        }
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
