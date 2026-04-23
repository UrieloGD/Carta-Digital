/* Music Player - Plantilla 8 K-pop Demon Hunters x Raya
   Arquitectura idéntica a la plantilla de referencia
   ------------------------------------------------------------ */

class MusicPlayer {
    constructor(youtubeUrl, autoplay = false, volume = 0.7) {
        this.youtubeUrl     = youtubeUrl;
        this.autoplay       = autoplay;
        this.volume         = volume;
        this.player         = null;
        this.isPlaying      = false;
        this.isReady        = false;
        this.userInteracted = false;
        this.videoTitle     = 'Cargando...';

        if (youtubeUrl && this.isValidYouTubeUrl(youtubeUrl)) {
            this.setupUserInteraction();
            this.loadYouTubeAPI();
            this.fetchVideoTitle();
        } else {
            console.warn('[MusicPlayer] URL de YouTube inválida o vacía');
        }
    }

    isValidYouTubeUrl(url) {
        const regex = /^(https?:\/\/)?(www\.)?(youtube\.com\/(watch\?v=|embed\/|shorts\/)|youtu\.be\/)[\w-]+/;
        return regex.test(url);
    }

    async fetchVideoTitle() {
        const videoId = this.extractVideoId(this.youtubeUrl);
        if (!videoId) return;
        try {
            const res  = await fetch(`https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=${videoId}&format=json`);
            const data = await res.json();
            this.videoTitle = data.title || 'Música de fondo';
            const titleEl = document.querySelector('.music-title');
            if (titleEl) { titleEl.textContent = this.videoTitle; titleEl.title = this.videoTitle; }
        } catch { this.videoTitle = 'Música de fondo'; }
    }

    setupUserInteraction() {
        const handle = () => {
            this.userInteracted = true;
            if (this.autoplay && this.isReady && !this.isPlaying) setTimeout(() => this.play(), 100);
            document.removeEventListener('click',      handle);
            document.removeEventListener('touchstart', handle);
            document.removeEventListener('keydown',    handle);
            document.removeEventListener('scroll',     handle);
        };
        document.addEventListener('click',      handle, { passive: true });
        document.addEventListener('touchstart', handle, { passive: true });
        document.addEventListener('keydown',    handle, { passive: true });
        document.addEventListener('scroll',     handle, { passive: true });
    }

    loadYouTubeAPI() {
        if (window.YT && window.YT.Player) { this.createPlayer(); return; }
        window.onYouTubeIframeAPIReady = () => this.createPlayer();
        if (!document.getElementById('yt-iframe-api')) {
            const tag = document.createElement('script');
            tag.id  = 'yt-iframe-api';
            tag.src = 'https://www.youtube.com/iframe_api';
            document.head.appendChild(tag);
        }
    }

    extractVideoId(url) {
        const patterns = [
            /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/shorts\/)([^&\n?#]+)/,
            /youtube\.com\/watch\?.*v=([^&\n?#]+)/
        ];
        for (const p of patterns) {
            const m = url.match(p);
            if (m && m[1]) return m[1];
        }
        return null;
    }

    createPlayer() {
        const videoId = this.extractVideoId(this.youtubeUrl);
        if (!videoId) { console.error('[MusicPlayer] No se pudo extraer el videoId'); return; }

        let container = document.getElementById('youtube-music-player');
        if (!container) {
            container = document.createElement('div');
            container.id = 'youtube-music-player';
            container.style.cssText = 'position:fixed;top:-1000px;left:-1000px;width:1px;height:1px;opacity:0;pointer-events:none;z-index:-1;';
            document.body.appendChild(container);
        }

        try {
            this.player = new YT.Player('youtube-music-player', {
                height: '1', width: '1', videoId,
                playerVars: { autoplay:0, controls:0, disablekb:1, fs:0, modestbranding:1, rel:0, showinfo:0, loop:1, playlist:videoId, mute:0, start:0 },
                events: {
                    onReady:       (e) => this.onPlayerReady(e),
                    onStateChange: (e) => this.onPlayerStateChange(e),
                    onError:       (e) => this.onPlayerError(e)
                }
            });
        } catch (err) { console.error('[MusicPlayer] Error creando player:', err); }
    }

    onPlayerReady(event) {
        this.isReady = true;
        event.target.setVolume(Math.round(this.volume * 100));
        this.createMusicControls();
        if (this.autoplay) setTimeout(() => this.play(), 1500);
    }

    onPlayerStateChange(event) {
        this.isPlaying = (event.data === YT.PlayerState.PLAYING);
        this.updateControlsUI();
        if (event.data === YT.PlayerState.ENDED) {
            setTimeout(() => { if (this.player) { this.player.seekTo(0); this.play(); } }, 800);
        }
    }

    onPlayerError(event) {
        const errors = { 2:'ID inválido', 5:'Error HTML5', 100:'Video privado/no encontrado', 101:'No permite embed', 150:'No permite embed' };
        console.error('[MusicPlayer] Error:', errors[event.data] || 'Desconocido');
    }

    createMusicControls() {
        document.querySelector('.music-controls')?.remove();

        const controls = document.createElement('div');
        controls.className = 'music-controls';
        controls.innerHTML = `
            <div class="music-player-widget">
                <button class="music-toggle" id="musicToggle" title="Reproducir/Pausar música">
                    <span class="play-icon">▶</span>
                </button>
                <div class="music-info">
                    <span class="music-title" title="${this.videoTitle}">${this.videoTitle}</span>
                    <span class="music-status">Cargando...</span>
                </div>
                <div class="music-volume">
                    <span class="volume-icon">♪</span>
                    <input type="range" id="volumeSlider" min="0" max="100"
                           value="${Math.round(this.volume * 100)}" class="volume-slider" title="Volumen">
                </div>
                <button class="music-minimize" id="musicMinimize" title="Minimizar reproductor">
                    <span>−</span>
                </button>
            </div>`;
        document.body.appendChild(controls);

        document.getElementById('musicToggle').addEventListener('click', (e) => {
            e.preventDefault();
            controls.classList.contains('minimized') ? this.expandPlayer() : this.toggle();
        });
        document.getElementById('volumeSlider').addEventListener('input', (e) => this.setVolume(e.target.value / 100));
        document.getElementById('musicMinimize').addEventListener('click', (e) => { e.preventDefault(); this.toggleMinimize(); });
    }

    toggleMinimize() { document.querySelector('.music-controls')?.classList.contains('minimized') ? this.expandPlayer() : this.minimizePlayer(); }
    minimizePlayer() { document.querySelector('.music-controls')?.classList.add('minimized'); }
    expandPlayer()   { document.querySelector('.music-controls')?.classList.remove('minimized'); }

    play()   { if (!this.player || !this.isReady) return; try { this.player.playVideo(); } catch(e){} }
    pause()  { if (!this.player || !this.isReady) return; try { this.player.pauseVideo(); } catch(e){} }
    toggle() { this.userInteracted = true; this.isPlaying ? this.pause() : this.play(); }
    setVolume(v) { this.volume = v; if (this.player && this.isReady) try { this.player.setVolume(Math.round(v*100)); } catch(e){} }

    updateControlsUI() {
        const controls = document.querySelector('.music-controls');
        const icon     = document.querySelector('#musicToggle .play-icon');
        const status   = document.querySelector('.music-status');
        if (icon)    icon.textContent   = this.isPlaying ? '⏸' : '▶';
        if (status)  status.textContent = this.isPlaying ? 'Reproduciendo' : 'Pausado';
        if (controls) controls.classList.toggle('playing', this.isPlaying);
    }
}

/* ── Función global de inicialización ── */
window.initMusicPlayer = function(youtubeUrl, autoplay, volume) {
    if (!youtubeUrl) { console.warn('[MusicPlayer] Sin URL'); return; }
    const init = () => { window.musicPlayerInstance = new MusicPlayer(youtubeUrl, autoplay, volume); };
    document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', init) : init();
};