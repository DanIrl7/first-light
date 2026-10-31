// Music player for the chart song. The video starts loading, muted, as soon as the page does, behind the
// "click to begin" cover (gate.js). That click is the visitor's permission to play sound, so it turns the sound on.
// The song then plays all the way down the page, getting a little quieter away from its own section.
// A button in the corner turns it off and on.
(function () {
    const section = document.getElementById('music');
    const holder = document.getElementById('music-player');
    const status = document.getElementById('music-status');
    const toggle = document.getElementById('sound-toggle');
    const gate = window.bornGate || null;

    if (!section || !holder) {
        return;
    }

    if (gate) {
        gate.attached = true;
    }

    const videoId = section.dataset.videoId;
    const MAX_VOLUME = 80;
    const MIN_VOLUME = 50; // the quietest it gets, however far the section is scrolled away

    const videoFrame = holder.closest('.video');

    let player = null;
    let playerReady = false;
    let soundOn = false;
    let readyTimer = null;

    // The frame shimmers until the video is actually playing (or it's clear it won't start by itself).
    function markReady() {
        clearTimeout(readyTimer);

        if (videoFrame) {
            videoFrame.classList.add('is-ready');
        }
    }

    function setStatus(text) {
        if (status) {
            status.textContent = text;
        }
    }

    function setSound(on) {
        soundOn = on;

        if (toggle) {
            toggle.classList.toggle('is-muted', !on);
            toggle.setAttribute('aria-pressed', String(on));
            toggle.setAttribute('aria-label', on ? 'Turn the music off' : 'Turn the music on');
        }

        setStatus(on ? '' : 'The radio is off. Use the button in the corner to turn it on.');
    }

    function loadApi() {
        window.onYouTubeIframeAPIReady = createPlayer;
        setStatus('Tuning the radio…');

        const script = document.createElement('script');
        script.src = 'https://www.youtube.com/iframe_api';
        script.onerror = () => {
            markReady();
            fail('The soundtrack didn\'t make the trip. Check your connection.', 'You can search for the song on YouTube instead.');
        };
        document.head.appendChild(script);
    }

    function createPlayer() {
        player = new YT.Player(holder, {
            videoId: videoId,
            width: '100%',
            height: '100%',
            playerVars: {
                autoplay: 1,
                mute: 1, // starts muted; the click on the cover turns the sound on
                loop: 1,
                playlist: videoId, // YouTube only loops a video when it's also listed as the playlist
                playsinline: 1,
                rel: 0,
                modestbranding: 1,
                controls: 0, // no on-screen player controls
                disablekb: 1, // no keyboard shortcuts either
                iv_load_policy: 3, // no annotation cards over the video
                fs: 0, // no fullscreen button
            },
            events: {
                onReady: () => {
                    playerReady = true;
                    // A request, not a guarantee: YouTube may choose a different quality if 720p isn't available.
                    player.setPlaybackQuality('hd720');
                    // Captions are turned off here, since YouTube can show them by default.
                    player.unloadModule('captions');
                    player.unloadModule('cc');
                    updateVolume();
                    player.playVideo();

                    // Some browsers won't start a video on their own. The click on the cover starts it then,
                    // so don't keep the visitor waiting for something only that click can do.
                    readyTimer = setTimeout(() => {
                        markReady();

                        if (gate) {
                            gate.ready();
                        }
                    }, 6000);
                },
                onStateChange: (event) => {
                    // 1 means playing.
                    if (event.data === 1) {
                        markReady();

                        if (gate) {
                            gate.ready();
                        }
                    }

                    // 0 means ended. Looping normally handles it; this is the back-up.
                    if (event.data === 0) {
                        player.playVideo();
                    }
                },
                onError: (event) => {
                    markReady();
                    console.warn('YouTube player error', event.data);
                    fail(errorMessage(event.data));
                },
            },
        });
    }

    // Nothing can be played: let the visitor through, and take the sound button away.
    function fail(message, extra) {
        if (toggle) {
            toggle.hidden = true;
        }

        if (gate) {
            gate.fail(message);
        }

        setStatus(extra ? message + ' ' + extra : message);
    }

    // Codes from YouTube's IFrame API: https://developers.google.com/youtube/iframe_api_reference#Events
    function errorMessage(code) {
        const reasons = {
            2: 'The video ID is invalid.',
            5: 'The browser couldn\'t play this video.',
            100: 'This video was removed or is private.',
            101: 'The video owner doesn\'t allow it to be played on other sites.',
            150: 'The video owner doesn\'t allow it to be played on other sites.',
        };

        return 'The soundtrack didn\'t make the trip. ' + (reasons[code] || 'This video can\'t be played here.') + ' You can search for the song on YouTube instead.';
    }

    function turnOnSound() {
        if (!playerReady) {
            return;
        }

        player.unMute();
        // Also starts the video if the browser wouldn't let it begin on its own.
        player.playVideo();
        setSound(true);
        updateVolume();
    }

    function turnOffSound() {
        if (!playerReady) {
            return;
        }

        // Muted rather than paused, so the video in its section keeps playing.
        player.mute();
        setSound(false);
    }

    // The click on the cover. It runs inside that click, which is what lets the browser play sound.
    window.addEventListener('gate:sound', turnOnSound);

    // Continuing without sound leaves the video playing, muted. Say so, unless the video never loaded.
    window.addEventListener('gate:open', () => {
        if (playerReady && !soundOn) {
            setSound(false);
        }
    });

    if (toggle) {
        toggle.addEventListener('click', () => {
            if (soundOn) {
                turnOffSound();
            } else {
                turnOnSound();
            }
        });
    }

    // Full volume while the section is on screen, easing down to the minimum over one screen height away.
    function updateVolume() {
        if (!player || !playerReady) {
            return;
        }

        const rect = section.getBoundingClientRect();
        const viewport = window.innerHeight;
        let distance = 0;

        if (rect.bottom < 0) {
            distance = -rect.bottom;
        } else if (rect.top > viewport) {
            distance = rect.top - viewport;
        }

        const fade = Math.min(1, distance / viewport);
        player.setVolume(Math.round(MAX_VOLUME - (MAX_VOLUME - MIN_VOLUME) * fade));
    }

    let frame = null;

    window.addEventListener('scroll', () => {
        if (frame === null) {
            frame = requestAnimationFrame(() => {
                updateVolume();
                frame = null;
            });
        }
    }, { passive: true });

    window.addEventListener('resize', updateVolume);

    // With smooth scrolling the page keeps gliding for a moment after the scroll events stop,
    // so check the volume once more when it settles.
    if (window.ScrollTrigger) {
        ScrollTrigger.addEventListener('scrollEnd', updateVolume);
    }

    // Pause when the tab is hidden, so the video doesn't keep playing in the background, and carry on when it's back.
    document.addEventListener('visibilitychange', () => {
        if (!playerReady) {
            return;
        }

        if (document.hidden) {
            player.pauseVideo();
        } else {
            player.playVideo();
        }
    });

    setSound(false);
    loadApi();
})();
