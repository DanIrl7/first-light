// Music player for the chart song. The video starts as soon as the page loads, muted, because browsers
// only allow sound after the visitor interacts with the page. The first click or key press turns the sound on.
// The video loops inside its section, and its volume eases down as the section scrolls off the screen.
(function () {
    const section = document.getElementById('music');
    const button = document.getElementById('music-play');
    const holder = document.getElementById('music-player');
    const status = document.getElementById('music-status');

    if (!section || !button || !holder) {
        return;
    }

    const videoId = section.dataset.videoId;
    const MAX_VOLUME = 80;
    const MIN_VOLUME = 50; // the quietest it gets, however far the section is scrolled away

    const videoFrame = holder.closest('.video');

    let player = null;
    let playerReady = false;
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

    function loadApi() {
        window.onYouTubeIframeAPIReady = createPlayer;
        setStatus('Loading the music video…');

        const script = document.createElement('script');
        script.src = 'https://www.youtube.com/iframe_api';
        script.onerror = () => {
            markReady();
            setStatus('The music player couldn\'t load. Check your connection, or search for the song on YouTube.');
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
                mute: 1, // starts muted; the sound is turned on after the visitor's first click or key press
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
                    setStatus('Starting the video…');

                    // Some browsers won't start a video on their own. Don't leave the frame shimmering forever.
                    readyTimer = setTimeout(() => {
                        markReady();
                        setStatus('The video is ready. Click "Turn on sound" to start it.');
                    }, 6000);
                },
                onStateChange: (event) => {
                    // 1 means playing.
                    if (event.data === 1) {
                        markReady();
                        setStatus(button.hidden
                            ? 'The video loops and gets quieter as you scroll away from it.'
                            : 'Playing without sound. Click anywhere to turn it on.');
                    }
                },
                onError: (event) => {
                    markReady();
                    button.hidden = true;
                    console.warn('YouTube player error', event.data);
                    setStatus(errorMessage(event.data));
                },
            },
        });
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

        return (reasons[code] || 'This video can\'t be played here.') + ' You can search for the song on YouTube instead.';
    }

    function turnOnSound() {
        if (!playerReady) {
            return;
        }

        player.unMute();
        // Also starts the video if the browser wouldn't let it begin on its own.
        player.playVideo();
        button.hidden = true;
        setStatus('The video loops and gets quieter as you scroll away from it.');
        updateVolume();
    }

    button.addEventListener('click', turnOnSound);

    // The first click or key press anywhere on the page turns the sound on. Browsers accept that as the
    // visitor's permission, so the sound isn't blocked. Once it's on, these listeners are removed.
    function onFirstInteraction() {
        if (!playerReady) {
            return;
        }

        document.removeEventListener('pointerdown', onFirstInteraction);
        document.removeEventListener('keydown', onFirstInteraction);
        turnOnSound();
    }

    document.addEventListener('pointerdown', onFirstInteraction);
    document.addEventListener('keydown', onFirstInteraction);

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

    // Pause when the tab is hidden, so the video doesn't keep playing in the background.
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && playerReady) {
            player.pauseVideo();
        }
    });

    loadApi();
})();
