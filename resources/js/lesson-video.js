// Load the pinned SDK only on pages with a video and near the viewport.
let sdkPromise;
function loadSdk(version) {
    if (sdkPromise) return sdkPromise;
    const base = `https://g.alicdn.com/apsara-media-box/imp-web-player/${version}`;
    sdkPromise = Promise.all([
        loadAsset('link', `${base}/skins/default/aliplayer-min.css`),
        loadAsset('script', `${base}/aliplayer-min.js`),
    ]).then(() => {
        if (typeof window.Aliplayer !== 'function') throw new Error('Player SDK unavailable');
        return window.Aliplayer;
    }).catch(error => {
        sdkPromise = null;
        throw error;
    });
    return sdkPromise;
}

function loadAsset(tag, url) {
    return new Promise((resolve, reject) => {
        const existing = [...document.querySelectorAll('[data-player-asset]')].find(node => node.dataset.playerAsset === url);
        if (existing?.dataset.loaded === 'true') return resolve();
        const node = document.createElement(tag);
        node.dataset.playerAsset = url;
        if (tag === 'link') {
            node.rel = 'stylesheet';
            node.href = url;
        } else {
            node.async = true;
            node.src = url;
        }
        const timer = setTimeout(fail, 15000);
        function fail() {
            clearTimeout(timer);
            node.remove();
            reject(new Error('Player asset failed to load'));
        }
        node.addEventListener('load', () => {
            clearTimeout(timer);
            node.dataset.loaded = 'true';
            resolve();
        }, {once: true});
        node.addEventListener('error', fail, {once: true});
        document.head.append(node);
    });
}

document.querySelectorAll('[data-lesson-video]').forEach(section => {
    const options = JSON.parse(section.dataset.playerOptions);
    const mount = section.querySelector('[data-video-mount]');
    const placeholder = section.querySelector('[data-video-placeholder]');
    const message = section.querySelector('[data-video-message]');
    const retry = section.querySelector('[data-video-retry]');
    const fallback = section.querySelector('[data-video-fallback]');
    let player;
    let loading = false;
    let disposed = false;
    let readyTimer;

    function destroyPlayer() {
        clearTimeout(readyTimer);
        if (player) {
            try { player.dispose(); } catch { /* Also clean up partial SDK initialization. */ }
            player = null;
        }
        mount.replaceChildren();
    }

    function fail() {
        clearTimeout(readyTimer);
        loading = false;
        placeholder.hidden = false;
        message.textContent = '视频暂时无法播放，可以重试或先完成图文步骤。';
        retry.hidden = false;
        fallback.hidden = false;
        section.dataset.videoState = 'error';
    }

    async function init() {
        if (loading || disposed) return;
        loading = true;
        destroyPlayer();
        placeholder.hidden = false;
        retry.hidden = true;
        fallback.hidden = true;
        message.textContent = '正在准备视频…';
        section.dataset.videoState = 'loading';
        try {
            const Aliplayer = await loadSdk(options.version);
            if (disposed) return;
            const config = {
                id: mount.id,
                source: options.source,
                cover: options.cover || '',
                width: '100%',
                height: '100%',
                isLive: false,
                autoplay: false,
                preload: false,
                playsinline: true,
                useH5Prism: true,
                language: 'zh-cn',
                showBarTime: 3000,
                speedLevels: [0.75, 1, 1.25, 1.5, 2].map(speed => ({key: speed, text: speed === 1 ? '原速' : `${speed}x`})),
            };
            if (options.license.domain && options.license.key) config.license = options.license;
            readyTimer = setTimeout(fail, 20000);
            const showReady = () => {
                clearTimeout(readyTimer);
                loading = false;
                if (section.dataset.videoState === 'error') return;
                placeholder.hidden = true;
                section.dataset.videoState = 'ready';
            };
            // With preload disabled, media "ready" waits for a user to press play.
            player = new Aliplayer(config, showReady);
            player.on('uiReady', showReady);
            player.on('error', fail);
            // Watching a video does not submit acceptance or award completion points.
        } catch {
            if (!disposed) fail();
        }
    }

    retry.addEventListener('click', init);
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => {
            if (entries.some(entry => entry.isIntersecting)) {
                observer.disconnect();
                init();
            }
        }, {rootMargin: '200px'});
        observer.observe(section);
    } else {
        init();
    }
    window.addEventListener('pagehide', event => {
        if (event.persisted) return;
        disposed = true;
        destroyPlayer();
    });
});
