// Music player for the chart song. The video loops inside its section, and its volume fades
// out as the section scrolls off the screen. YouTube's player script loads only when the
// visitor presses Play, so nothing is fetched from YouTube on page load.
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

    let player = null;
    let playerReady = false;
    let playRequested = false;
    let apiRequested = false;

    function setStatus(text) {
        if (status) {
            status.textContent = text;
        }
    }

    function loadApi() {
        if (apiRequested) {
            return;
        }

        apiRequested = true;
        window.onYouTubeIframeAPIReady = createPlayer;

        const script = document.createElement('script');
        script.src = 'https://www.youtube.com/iframe_api';
        script.onerror = () => setStatus('The music player couldn\'t load. Check your connection, or search for the song on YouTube.');
        document.head.appendChild(script);
    }

    function createPlayer() {
        player = new YT.Player(holder, {
            videoId: videoId,
            width: '100%',
            height: '100%',
            playerVars: {
                loop: 1,
                playlist: videoId, // YouTube only loops a video when it's also listed as the playlist
                playsinline: 1,
                rel: 0,
                modestbranding: 1,
                controls: 0, // no on-screen player controls
                disablekb: 1, // no keyboard shortcuts either
            },
            events: {
                onReady: () => {
                    playerReady = true;
                    // A request, not a guarantee: YouTube may choose a different quality if 720p isn't available.
                    player.setPlaybackQuality('hd720');
                    updateVolume();

                    if (playRequested) {
                        startPlayback();
                    }
                },
                onError: (event) => {
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

    function startPlayback() {
        player.playVideo();
        setStatus('The video loops and fades out as you scroll away from it.');
    }

    button.addEventListener('click', () => {
        playRequested = true;
        button.hidden = true;

        if (playerReady) {
            startPlayback();
        } else {
            loadApi();
        }
    });

    // Full volume while the section is on screen, fading to silent over one screen height away.
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

        const fade = Math.max(0, 1 - distance / viewport);
        player.setVolume(Math.round(fade * MAX_VOLUME));
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
})();
