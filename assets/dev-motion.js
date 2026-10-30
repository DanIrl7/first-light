// Development only: lets the site animate even when the computer asks for reduced motion
// (for example Windows with "Animation effects" turned off). It does nothing except on localhost.
//   http://localhost:8085/?motion=on   turns it on for this tab
//   http://localhost:8085/?motion=off  turns it off again
// Load this in <head>, before any script that checks prefers-reduced-motion.
(function () {
    const host = location.hostname;

    if (host !== 'localhost' && host !== '127.0.0.1' && host !== '[::1]') {
        return;
    }

    const KEY = 'forceMotion';
    let on = false;

    try {
        const asked = new URLSearchParams(location.search).get('motion');

        if (asked === 'on') {
            sessionStorage.setItem(KEY, '1');
        } else if (asked === 'off') {
            sessionStorage.removeItem(KEY);
        }

        on = sessionStorage.getItem(KEY) === '1';
    } catch (error) {
        return;
    }

    if (!on) {
        return;
    }

    // JavaScript checks: make "no preference" always match and "reduce" never match.
    const realMatchMedia = window.matchMedia.bind(window);

    window.matchMedia = (query) => realMatchMedia(
        String(query)
            .replace(/\(\s*prefers-reduced-motion\s*:\s*no-preference\s*\)/g, '(min-width: 0px)')
            .replace(/\(\s*prefers-reduced-motion\s*:\s*reduce\s*\)/g, 'not all')
    );

    // CSS checks can't be changed, so copy each "no-preference" block into a plain style rule.
    document.addEventListener('DOMContentLoaded', () => {
        const rules = [];

        for (const sheet of document.styleSheets) {
            let list;

            try {
                list = sheet.cssRules;
            } catch (error) {
                continue; // a stylesheet from another site (the fonts) can't be read
            }

            for (const rule of list) {
                if (rule.type === CSSRule.MEDIA_RULE && /no-preference/.test(rule.conditionText)) {
                    for (const inner of rule.cssRules) {
                        rules.push(inner.cssText);
                    }
                }
            }
        }

        const style = document.createElement('style');
        style.textContent = rules.join('\n');
        document.head.appendChild(style);
    });

    console.info('Dev motion is on: animations run even if the system asks for reduced motion. Add ?motion=off to stop.');
})();
